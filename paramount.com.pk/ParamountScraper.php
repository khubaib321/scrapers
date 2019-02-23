<?php
require_once __DIR__ . '/../ScraperBase.php';

class ParamountScraper extends ScraperBase
{
    public $infoToGrab = [
        'List Price:',
        'Author:',
        'ISBN:',
        'Year:',
        'Category:',
        'Edition:',
        'Format:',
        'Language:',
        'Pages:',
    ];

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

            $links = $this->html->find('td a');
            foreach ($links as $a) {
                if (!in_array(strtolower($a->href), $this->visited)) {
                    if (strpos($a->href, 'title=') !== false) {
//                        echo $a->href, $this->newLine;
                        $this->productLinks[] = $a->href;
                    } elseif (!isset($this->toBeVisited[$a->href]) || $this->toBeVisited[$a->href] == false) {
//                        echo $a->href, $this->newLine;
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
            $grabAfter   = -1;
            $productInfo = [
                'Title:' => '',
                'Description:' => '',
                'List Price:' => '',
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
                        $grabAfter   = ($tdContent === 'List Price:') ? 0 : 1;
//                        echo $tdContent, ' ';
                    } elseif ($grabAfter === 0 && empty($productInfo[$grabbingNow])) {
                        $grabAfter                 = -1;
//                        echo $tdContent, $this->newLine;
                        $productInfo[$grabbingNow] = $tdContent;
                    } else {
                        --$grabAfter;
                    }
                }
            }

            // to avoid duplicates
            if (in_array(trim($productInfo['ISBN:']), $this->bookISBNS)) {
                $validExport = false;
            }

            if (!empty($productInfo['ISBN:'])) {
                $this->currentISBN = $productInfo['ISBN:'];
                // if valid isbn
                $images = $this->html->find("img[src^=images/books/{$productInfo['ISBN:']}]");
                foreach ($images as $img) {
                    if (!empty($img->src)) {
                        $this->downloadImage($img->src);
                        $productInfo['Image:'] = $img->src; // save image path
                        break;
                    }
                }
            } else {
                echo "\t\tFailed to find image for ISBN '{$productInfo['ISBN:']}'", $this->newLine;
            }

            $productInfo['Url:'] = "{$this->baseUrl}/{$page}";    // save url to correct anamolies
            if ($validExport) {
                $this->bookISBNS[]       = trim($this->currentISBN);
                $this->scrapedProducts[] = array_values($productInfo);
            } else {
                echo "\t\tProduct Already Scraped => ISBN: {$this->currentISBN}", $this->newLine;
            }
            return $validExport;
        } catch (Exception $ex) {
            echo print_r($ex, 1);
            return false;
        }
    }
}