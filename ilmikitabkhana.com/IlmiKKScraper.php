<?php
require_once __DIR__ . '/../ScraperBase.php';

class IlmiKKScraper extends ScraperBase
{

    public $infoToGrab = [
        'Price',
        'Writer:',
        'Publisher:',
        'ISBN:',
        'Category:',
        'Product Code:',
        'Availability:',
        'Pages:',
    ];

    public function __construct($baseUrl = '')
    {
        parent::__construct($baseUrl);
        $this->currentDIR = 'ilmikitabkhana.com';
    }

    public function testFetch($page, $i = 0)
    {
        $this->loadPage($page, $i);

        if (!method_exists($this->html, 'find')) {
            return false;
        }
        return true;
    }

    public function scrapeProduct($page, $i = 0)
    {
        try {
            $page = str_replace(' ', '%20', trim($page));
            $this->loadPage($page, $i);

            $this->currentISBN = '';

            if (!method_exists($this->html, 'find')) {
                return false;
            }

            $productInfo = [
                'Title' => '',
                'Description' => '',
                'Additional Info' => '',
                'Price' => '',
                'Category' => '',
                'Image' => '',
                'Url' => '',
            ];

            $productInfo['Title'] = trim($this->html->find('h1.title-product')[0]->plaintext);
            $productInfo['Description'] = trim($this->html->find('#tab-description')[0]->plaintext);
            $productInfo['Additional Info'] = trim($this->html->find('ul.list-unstyled.description')[0]->plaintext);
            $this->currentProduct = $productInfo['Title'];

            $price = trim($this->html->find('div.price')[0]->plaintext);
            $productInfo['Price'] = $price;

            $category = '';
            $breadcrumbs = $this->html->find('ul.breadcrumb li');
            foreach ($breadcrumbs as $i => $bc) {
                if ($i == 0 || $i == (count($breadcrumbs) - 1)) {
                    continue;
                }
                if (empty($category)) {
                    $category .= $bc->plaintext;
                } else {
                    $category .= ' > ' . $bc->plaintext;
                }
            }

            $productInfo['Category'] = strtoupper($category);
            $productInfo['Url'] = $page;
            $this->productID = $productInfo['Title'];

            $validExport = false;
            if (!empty($this->productID) &&
                !in_array($this->productID, $this->bookISBNS)
            ) {
                $validExport = true;
                $img = $this->html->find('div#img-detail a');
                if (!empty($img)) {
                    $this->downloadImage($img[0]->href);
                }
                $productInfo['Image'] = "images/{$this->productID}.jpg";
                $this->bookISBNS[] = $this->productID;
                $this->scrapedProducts[] = $productInfo;
            } else {
                echo "\t\tProduct Already Scraped => ID: {$this->productID}", $this->newLine;
            }
//            echo print_r($productInfo, 1), $this->newLiness;
            return $validExport;
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
        // load any other categories
        $this->loadToBeVisited('ul#accordion a', '?limit=100');

        // load current subcategories (already starting with a category)
        if (!$this->loadToBeVisited('div.refine-search a', '?limit=100')) {
            $this->loadProductLinks('div.#products div.product-meta h4.name a');
        }
    }
}
