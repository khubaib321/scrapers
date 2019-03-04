<?php
require_once __DIR__ . '/../ScraperBase.php';

class ParamountScraper extends ScraperBase
{

    public $listFound = false;
    public $infoToGrab = [
        'Price:',
        'Author:',
        'ISBN:',
        'Year:',
        'Category:',
        'Edition:',
        'Format:',
        'Language:',
        'Pages:',
    ];
    public $listUrls = [];

    public function __construct($baseUrl = '')
    {
        parent::__construct($baseUrl);
        $this->currentDIR = 'paramount.com.pk';
        $this->categoryNameMap = [
            '01' => 'business',
            '02' => 'children books',
            '03' => 'computer science',
            '04' => 'engineering',
            '05' => 'general interest',
            '06' => 'medical',
            '07' => 'science',
            '08' => 'social sciences',
        ];
    }

    /**
     * Fetches all product links from a page
     * @param string $page url to get products from
     * @param int $i to identify product number
     * @return int
     */
    public function fetchProductUrls($page, $i = 0)
    {
        try {
            $this->loadPage($page, $i);

            if (!method_exists($this->html, 'find')) {
                return 0;
            }

            // make a list
            $paginationImgs = $this->html->find('td a');
            foreach ($paginationImgs as $a) {
                if (strpos(strtolower(trim($a->plaintext)), 'make a list') !== false &&
                    (!isset($this->toBeVisited[$a->href]) || $this->toBeVisited[$a->href] == false)) {
                    $this->listUrls[] = $a->href;
                    break;
                }
            }
            // category pages
            $visitLinks = $this->html->find('tr.LeftCatSub td a');
            foreach ($visitLinks as $a) {
                if (!in_array(strtolower($a->href), $this->visited)) {
                    if (!empty($a->title) &&
                        strpos($a->href, 'Cat=04') !== false &&
                        (!isset($this->toBeVisited[$a->href]) || $this->toBeVisited[$a->href] == false)
                    ) {
                        $this->toBeVisited[$a->href] = true;
                    }
                }
            }
        } catch (Exception $ex) {
            echo print_r($ex, 1);
            return 0;
        }
        return count($this->productLinks);
    }

    /**
     * Get all product information from its page
     * @param url $page
     * @param int $i product number
     * @return array
     */
    public function scrapeProduct($page, $i = 0)
    {
        try {
            $this->loadPage($page, $i);
            $grabbingNow = '';
            $grabAfter = -1;
            $productInfo = [
                'Title:' => '',
                'Description:' => '',
                'Price:' => '',
                'Author:' => '',
                'ISBN:' => '',
                'Year:' => '',
                'Category:' => '',
                'Edition:' => '',
                'Format:' => '',
                'Language:' => '',
                'Pages:' => '',
                'Image:' => '',
                'Url:' => '',
            ];
            $validExport = true;

            $this->currentISBN = '';

            if (!method_exists($this->html, 'find')) {
                return [];
            }

            $title = $this->html->find('meta[name=title]');
            foreach ($title as $t) {
                $productInfo['Title:'] = $t->content;
//                echo 'TITLE:', $t->content, $this->newLine;
            }
            $description = $this->html->find('meta[name=description]');
            foreach ($description as $d) {
                $productInfo['Description:'] = $d->content;
//                echo 'DESCRIPTION:', $d->content, $this->newLine;
            }

            $f1tables = $this->html->find('table#f1');
            foreach ($f1tables as $table) {
                foreach ($table->find('td') as $td) {
                    $tdContent = str_replace('&nbsp;', '', $td->plaintext);
                    if (in_array($tdContent, $this->infoToGrab)) {
                        $grabbingNow = $tdContent;
                        $grabAfter = ($tdContent === 'Price:') ? 0 : 1;
//                        echo $tdContent, ' ';
                    } elseif ($grabAfter === 0 && empty($productInfo[$grabbingNow])) {
                        $grabAfter = -1;
//                        echo $tdContent, $this->newLine;
                        $productInfo[$grabbingNow] = $tdContent;
                    } else {
                        --$grabAfter;
                    }
                }
            }

            $this->productID = empty($productInfo['ISBN:']) ? $productInfo['Title:'] : $productInfo['ISBN:'];
            $this->currentISBN = $productInfo['ISBN:'];

            // to avoid duplicates
            if (in_array(trim($this->productID), $this->bookISBNS)) {
                $validExport = false;
            }

            if (!empty($this->productID)) {
                // if valid isbn
                $images = $this->html->find("img[src^=images/books/{$this->productID}]");
                foreach ($images as $img) {
                    if (!empty($img->src)) {
                        $this->downloadImage($img->src);
                        $productInfo['Image:'] = $img->src; // save image path
                        break;
                    }
                }
            } else {
                echo "\t\tFailed to find image for ID '{$this->productID}'", $this->newLine;
            }
            $categoryBase = strtoupper($this->exportFile);
            $productInfo['Category:'] = "{$categoryBase} > " . $productInfo['Category:'];
            $productInfo['Url:'] = "{$this->baseUrl}/{$page}";    // save url to correct anamolies
            if ($validExport) {
                $this->bookISBNS[] = trim($this->productID);
                $this->scrapedProducts[] = array_values($productInfo);
            } else {
                echo "\t\tProduct Already Scraped => ID: {$this->productID}", $this->newLine;
            }
            return $validExport;
        } catch (Exception $ex) {
            echo print_r($ex, 1);
            return false;
        }
    }

    /**
     * Load all links to visit and scrape data from
     */
    protected function loadAllLinks()
    {
        // not needed for this class
    }

    public function grabProductsFromLists($exportFile)
    {
        try {
            $totalLists = count($this->listUrls);
            echo $this->newLine, "GRABBING PRODUCTS FROM {$totalLists} LISTS", $this->newLine;
            foreach ($this->listUrls as $href) {
                // get product links
                $href = str_replace(' ', '%20', $href);
                $this->loadPage($href);
                if (!method_exists($this->html, 'find')) {
                    continue;
                }
                $productTables = $this->html->find('table#f1');
                foreach ($productTables as $tables) {
                    $productLinks = $tables->find('a');
                    foreach ($productLinks as $a) {
                        if (strpos($a->href, 'isbn=') !== false &&
                            (!isset($this->toBeVisited[$a->href]) || $this->toBeVisited[$a->href] == false)
                        ) {
                            $this->productLinks[] = $a->href;
                        }
                    }
                }
            }
        } catch (Exception $ex) {
            echo print_r($ex, 1);
            return 0;
        }
        $this->startScraping($exportFile);
    }
}
