<?php
require_once __DIR__ . '/../ScraperBase.php';

class StationaryXScraper extends ScraperBase
{

    public $subCat = '';
    public $infoToGrab = [
        'price',
        'Category',
        'Manufacturer',
        'Color',
    ];

    public function __construct($baseUrl = '')
    {
        parent::__construct($baseUrl);
        $this->currentDIR = 'stationaryx.pk';
    }

    public function testFetch($page, $i = 0)
    {
        $this->loadPage($page, $i);

        if (!method_exists($this->html, 'find')) {
            return 0;
        }
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
                'Manufacturer' => '',
                'price' => '',
                'Color' => '',
                'Category' => '',
                'Image' => '',
                'Url' => '',
            ];

            $titleDiv = $this->html->find('div.product-name');
            $productInfo['Title'] = trim($titleDiv[0]->plaintext);

            $descDiv = $this->html->find('div.short-description');
            $productInfo['Description'] = trim($descDiv[0]->plaintext);

//            $priceDiv = $this->html->find('td.data.last span.price');
//            $productInfo['price'] = trim($priceDiv[0]->plaintext);

            $specTableRows = $this->html->find('table#product-attribute-specs-table tr');
            foreach ($specTableRows as $tr) {
                if (!in_array($tr->class, ['first odd', 'last odd'])) {
                    $infoType = trim($tr->find('th')[0]->plaintext);
                    if (in_array($infoType, $this->infoToGrab)) {
                        $productInfo[$infoType] = trim($tr->find('td')[0]->plaintext);
                    }
                }
            }

            $category = 'STATIONARY' . ' > ' . strtoupper($this->exportFile) . ' > ' . $this->subCat;
            $productInfo['Category'] = $category;
            $productInfo['Url'] = $page;

            $this->productID = $productInfo['Title'];

            $valid_export = false;
            if (!empty($this->productID) &&
                !in_array($this->productID, $this->bookISBNS)) {
                $valid_export = true;
                $imgHref = $this->html->find('a#zoom1')[0]->href;
                $this->downloadImage($imgHref);
                $imageName = preg_replace('/[^a-z0-9]+/', '-', strtolower($this->productID));
                $productInfo['Image'] = "images/{$imageName}.jpg";
                $this->bookISBNS[] = $this->productID;
                $this->scrapedProducts[] = $productInfo;
            }

            return $valid_export;
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
        $this->loadProductLinks('div.category-products h2.product-name a');
        // then try to get all pagination links
        $this->loadToBeVisited('div.pager div.pages li a');
    }

    /**
     * Load sub cats to iterate over them
     */
    public function loadSubCategories($page, $i = 0)
    {
        try {
            $page = str_replace('amp;', '', trim($page));
            $page = str_replace(' ', '%20', trim($page));
            $this->loadPage($page, $i);

            if (!method_exists($this->html, 'find')) {
                return false;
            }

            $this->loadToBeVisited('div#header-nav ul#nav li.nav-item.level0.nav-4.active.current.level-top div.nav-panel--dropdown.nav-panel a', '?limit=100');
        } catch (Exception $exc) {
            echo $exc->getTraceAsString(), $this->newLine;
            return false;
        }
    }

    /**
     * Scrape info from yet to be visited pages
     * @param $exportFile
     */
    public function scrapeRemainder($exportFile)
    {
        $this->exportFile = $exportFile;
        $nonVisitedCount = array_sum(array_values($this->toBeVisited));
        if (empty($nonVisitedCount) || $nonVisitedCount == 0) {
            return;
        }
        echo $this->newLine, "SCRAPE PRODUCTS FROM => {$nonVisitedCount} PAGES", $this->newLine;
        foreach ($this->toBeVisited as $href => $toBeVisited) {
            $this->subCat = '';
            if ($toBeVisited === 1) {
                $this->subCat = trim(substr(strtoupper(str_replace('-', ' ', parse_url($href)['path'])), 1));
                $this->fetchProductUrls($href);
                $this->toBeVisited[$href] = 0;
                $this->startScraping($exportFile);
            }
        }
        $this->scrapeRemainder($exportFile);
    }
}
