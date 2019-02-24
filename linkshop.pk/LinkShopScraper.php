<?php
require_once __DIR__.'/../ScraperBase.php';

class LinkShopScraper extends ScraperBase
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
        $this->currentDIR = 'linkshop.pk';
    }

    public function testFetch($page, $i = 0)
    {
        $this->loadPage($page, $i);

        if (!method_exists($this->html, 'find')) {
            return 0;
        }

        $links = $this->html->find('div.pages a');
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
                'ISBN:' => '',
                'Writer:' => '',
                'Publisher:' => '',
                'Category:' => '',
                'Product Code:' => '',
                'Availability:' => '',
                'Pages:' => '',
                'Image' => '',
                'Url' => '',
            ];

            $productInfo['Title'] = $this->html->find('h1.heading-title')[0]->plaintext;
            $details              = $this->html->find('div#product div.product-sold-count-text');
            foreach ($details as $dd) {
                $info = trim($dd->find('span')[0]->plaintext);
                if (in_array($info, $this->infoToGrab)) {
                    if (!empty($dd->find('a'))) {
                        $productInfo[$info] = $dd->find('a')[0]->plaintext;
                    } else {
                        $data = trim(str_replace($info, '', $dd->plaintext));
                        $productInfo[$info] = $data;
                    }
                    if ($info === 'ISBN:') {
                        $productInfo[$info] = str_replace('-', '', $productInfo[$info]);
                    }
                }
            }
            // NO ISBN FOR SOME NOVELS
            $this->currentISBN = $productInfo['ISBN:'];
            $productInfo['Url'] = $page;
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
        $this->loadProductLinks('div.product-thumb div.product-details h4.name a');
        // then try to get all pagination links
        $this->loadToBeVisited('div.row.pagination li a');
    }
}