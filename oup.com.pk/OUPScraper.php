<?php
require_once __DIR__.'/../ScraperBase.php';

class OUPScraper extends ScraperBase
{
    public $infoToGrab = [
        'Price',
        'Author',
        'ISBN',
        'Year of Publication',
        'Category',
        'Edition',
        'Binding',
        'Language',
        'Pages',
    ];

    public function __construct($baseUrl = '')
    {
        parent::__construct($baseUrl);
        $this->currentDIR = 'oup.com.pk';
    }

    public function testFetch($page, $i = 0)
    {
        $this->loadPage($page, $i);

        if (!method_exists($this->html, 'find')) {
            return 0;
        }

        $links = $this->html->find('div.pages a');
        foreach ($links as $a) {
            // first verify its a valid oup url
            if (strpos($a->href, $this->baseUrl) !== false &&
                !in_array($a->href, $this->productLinks) &&
                !in_array($a->href, $this->toBeVisited)
            ) {
                echo 'PAGINATION =>', $a->href, $this->newLine;
                $this->toBeVisited[] = $a->href;
            }
        }
    }

    public function scrapeProduct($page, $i = 0)
    {
        try {
            $this->loadPage($page, $i);

            $this->currentISBN = '';

            if (!method_exists($this->html, 'find')) {
                return 0;
            }

            $productInfo = [
                'Title' => '',
                'Description' => '',
                'Price' => '',
                'Author' => '',
                'ISBN' => '',
                'Year of Publication' => '',
                'Category' => '',
                'Edition' => '',
                'Binding' => '',
                'Language' => '',
                'Pages' => '',
                'Image' => '',
                'Url' => '',
            ];

            $productEssential = $this->html->find('div.product-essential');
            if (!empty($productEssential)) {
                // landed on a product page
                $title                = $productEssential[0]->find('div.product-name');
                $productInfo['Title'] = $title[0]->plaintext;

                // description
                $description = $productEssential[0]->find('div.short-description');
                foreach ($description as $desc) {
                    if (empty($productInfo['Author'])) {
                        $productInfo['Author'] = $desc->plaintext;
                    } else {
                        $productInfo['Description'] .= $desc->plaintext."\n";
                    }
                }

                $specTable = $productEssential[0]->find('table#product-attribute-specs-table');
                foreach ($specTable[0]->find('th') as $i => $th) {
                    if (in_array($th->plaintext, $this->infoToGrab)) {
                        $productInfo[$th->plaintext] = $specTable[0]->find('td',
                                $i)->plaintext;
                    }
                }

                $category = '';
                $lis      = $this->html->find('div.breadcrumbs li');
                foreach ($lis as $li) {
                    if (!in_array($li->class, ['home', 'product'])) {
                        if (empty($category)) {
                            $category .= trim($li->find('a')[0]->plaintext);
                        } else {
                            $category .= ' / '.trim($li->find('a')[0]->plaintext);
                        }
                    }
                }

                $productInfo['Category'] = $category;
                $price                   = $productEssential[0]->find('span.price');
                $productInfo['Price']    = $price[0]->plaintext;
                $productInfo['Url']      = $page;

                if (!empty($productInfo['ISBN'])) {
                    $this->currentISBN = $productInfo['ISBN'];
                }

                $productInfo['Image'] = "images/{$this->currentISBN}.jpg";
                $validExport          = false;
                if (!empty($this->currentISBN) &&
                    !in_array($this->currentISBN, $this->bookISBNS)
                ) {
                    $this->bookISBNS []      = $this->currentISBN;
                    $this->scrapedProducts[] = $productInfo;
                    // download image
                    $images                  = $productEssential[0]->find('img#image-main');
                    $this->downloadImage($images[0]->src);
                    $validExport             = true;
                } else {
                    echo "\t\tOUPScraper::scrapeProduct - Product Already Scraped => ISBN: {$this->currentISBN}", $this->newLine;
                }

//                echo print_r($productInfo, 1), $this->newLine;
                return $validExport;
            }
            // load all other links anyway
            $this->loadAllLinks();
        } catch (Exception $ex) {
            echo print_r($ex, 1);
            return 0;
        }
    }

    public function fetchProductUrls($page, $i = 0)
    {
        try {
            $this->loadPage($page, $i);

            if (!method_exists($this->html, 'find')) {
                return 0;
            }

            $this->loadAllLinks();
        } catch (Exception $ex) {
            echo print_r($ex, 1);
            return 0;
        }
    }

    /**
     * Load all links to visit and scrape data from
     */
    protected function loadAllLinks()
    {
        // first try to get all products
        $this->loadProductLinks('li.item.last a');
        // then try to get all pagination links
        $this->loadToBeVisited('div.pages a');
        // then get every remaining link
        $this->loadToBeVisited('a');
    }
}