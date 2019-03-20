<?php
require_once __DIR__ . '/../ScraperBase.php';

class FerozsonsScraper extends ScraperBase
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
        $this->currentDIR = 'ferozsons.com.pk';
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
                'Publisher' => '',
                'Author' => '',
                'Price' => '',
                'Category' => '',
                'Image' => '',
                'Url' => '',
            ];

            $titleDiv = $this->html->find('div.summary.entry-summary.single-product-info h1.product_title.entry-title');
            $productInfo['Title'] = trim($titleDiv[0]->plaintext);

            $descDiv = $this->html->find('div.summary.entry-summary.single-product-info div.short-description');
            $productInfo['Description'] = trim($descDiv[0]->plaintext);

            $priceDiv = $this->html->find('div.summary.entry-summary.single-product-info div.product-price');
            $productInfo['Price'] = trim(explode(';', $priceDiv[0]->plaintext)[2]);

            $category = '';
            $breadcrumbs = $this->html->find('ul#breadcrumbs li.item');
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

            $productInfo['Category'] = $category;
            $productInfo['Url'] = $page;
            
            $attrRows = $this->html->find('table.shop_attributes tr');
            foreach ($attrRows as $tr) {
                if (trim($tr->find('th')[0]->plaintext) === 'Publisher') {
                    $productInfo['Publisher'] = trim($tr->find('td')[0]->plaintext);
                } elseif (trim($tr->find('th')[0]->plaintext) === 'Author') {
                    $productInfo['Author'] = trim($tr->find('td')[0]->plaintext);
                }
            }

            $this->productID = $productInfo['Title'];

            $valid_export = false;
            if (!empty($this->productID) &&
                !in_array($this->productID, $this->bookISBNS)) {
                $valid_export = true;
                $imgHref = $this->html->find('div.woocommerce-product-gallery__image a')[0]->href;
                $this->downloadImage($imgHref);
                $imageName = preg_replace('/[^a-z0-9]+/', '-', strtolower($this->productID));
                $productInfo['Image'] = "images/{$imageName}.jpg";
                $this->bookISBNS[] = $this->productID;
                $this->scrapedProducts[] = $productInfo;
            }

//            echo print_r($productInfo, 1), $this->newLine;
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
        $this->loadProductLinks('div.product-content a');
        // then try to get all pagination links
        $this->loadToBeVisited('ul.page-numbers a');
    }

    /**
     * Load sub cats to iterate over them
     */
    public function getCategoryPages($page, $i = 0)
    {
        try {
            $page = str_replace(' ', '%20', trim($page));
            $this->loadPage($page, $i);

            if (!method_exists($this->html, 'find')) {
                return false;
            }

            $this->loadToBeVisited('ul#mega-menu-primary a');
        } catch (Exception $exc) {
            echo $exc->getTraceAsString(), $this->newLine;
            return false;
        }
    }
}
