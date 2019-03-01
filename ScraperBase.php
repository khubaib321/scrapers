<?php
require_once 'simple_html_dom.php';

abstract class ScraperBase
{

    public $currentProduct = '';
    public $productID = '';
    public $currentDIR = '';
    public $currentISBN = [];
    public $toBeVisited = [];
    public $productLinks = [];
    public $infoToGrab = [];
    public $visited = [];
    public $failedLinks = [];
    public $scrapedProducts = [];
    public $bookISBNS = [];
    public $baseUrl = '';
    public $newLine = "\n";
    public $fp = null;
    public $fpISBN = null;
    public $html = null;

    public function __construct($baseUrl = '')
    {
        $this->setBaseURL($baseUrl);
    }

    abstract public function scrapeProduct($page, $i = 0);

    abstract protected function loadAllLinks();

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
     * This will help avoiding navigating to external links
     * @param string $baseUrl
     */
    public function setBaseURL($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * Load previously saved ISBNs
     * @param string $filename
     */
    public function loadISBNS($filename)
    {
        $file = fopen($filename . '.csv', 'r');
        if ($file) {
            while (($line = fgetcsv($file)) !== FALSE) {
                if (!empty($line[0])) {
                    $this->bookISBNS[] = trim($line[0]);
                }
            }
            fclose($file);
        }
    }

    /**
     * Returns instance of file pointer
     * @param string $file name of the csv file
     * @return Object
     */
    public function getFilePointer($file)
    {
        if (!$this->fp ||
            !is_resource($this->fp)
        ) {
            $this->fp = fopen("{$file}.csv", 'a');
        }
        return $this->fp;
    }

    /**
     * Returns instance of file pointer
     * @param string $file name of the csv file
     * @return Object
     */
    public function getFilePointerISBN($file)
    {
        if (!$this->fpISBN ||
            !is_resource($this->fpISBN)
        ) {
            $this->fpISBN = fopen("{$file}.csv", 'a');
        }
        return $this->fpISBN;
    }

    /**
     * Loads page dom in $html variable
     * @param string $page url of the page
     * @param int $i to identify product number
     * @param int $retry to identify retry count
     * @return boolean
     */
    public function loadPage($page, $i = 0, $retry = 0)
    {
        if ($page !== $this->baseUrl) {
            // if not home page then remove base url because appended later
            $page = str_replace($this->baseUrl, '', $page);
        }
        if ($page[0] === '/') {
            $page = substr($page, 1);
        }
        if (empty($this->baseUrl) ||
            in_array(strtolower("{$this->baseUrl}/{$page}"), $this->visited)
        ) {
//            echo "{$i} => Already Visited {$this->baseUrl}/{$page} OR Invalid Url", $this->newLine;
            $this->html = null;
            return false;
        }
        $isbn = '';
        $query = '';
        $urlParts = parse_url("{$this->baseUrl}/{$page}");
        if (isset($urlParts['query'])) {
            parse_str($urlParts['query'], $query);
            if (isset($query['ISBN'])) {
                $isbn = $query['ISBN'];
            } else if (isset($query['isbn'])) {
                $isbn = $query['isbn'];
            } else if (isset($query['Isbn'])) {
                $isbn = $query['Isbn'];
            }
        }
        if (!empty($isbn) && in_array($isbn, $this->bookISBNS)) {
            echo "\t\tScraperBase::loadPage - Product Already Scraped => ISBN: {$isbn}", $this->newLine;
            $this->html = null;
            return false;
        }
        if ($i > 0) {
            $totalPages = count($this->productLinks);
            $statusText = "{$i} / {$totalPages}";
            echo "Product {$statusText} |{$isbn}| => {$this->baseUrl}/{$page} ...";
        } else {
            echo "Fetching Urls From => {$this->baseUrl}/{$page} ...";
        }
        try {
            $html = file_get_html("{$this->baseUrl}/{$page}");
            echo ' => DONE ', $this->newLine;
        } catch (Exception $ex) {
            echo "\t\t {$this->newLine} Failed to load => {$this->baseUrl}/{$page} {$this->newLine} {$this->newLine}";
            echo print_r($ex, 1);
            $this->html = null;
            return false;
        }
        $this->html = $html;
        if (!$this->html) {
            if ($retry < 3) {
                $retry++;
                echo "\t\tFailed to load => {$this->baseUrl}/{$page} {$this->newLine} {$this->newLine} - [{$retry}] - Retrying after 10 seconds.", $this->newLine;
                sleep(10);
                $this->loadPage($page, $i, $retry);
            } elseif (!in_array("{$this->baseUrl}/{$page}", $this->failedLinks)) {
                // What if there's no Internet? Hmmm
                echo "\t\tAdded to failed links => {$this->baseUrl}/{$page}", $this->newLine;
                $this->failedLinks[] = "{$this->baseUrl}/{$page}";
                $this->toBeVisited["{$this->baseUrl}/{$page}"] = false;
                return false;
            } else {
                /**
                 * Most probably an external link or broken link or something unavailable
                 * no need to visit again i think
                 */
                $this->toBeVisited["{$this->baseUrl}/{$page}"] = false;
                $this->visited[] = strtolower("{$this->baseUrl}/{$page}");
                return false;
            }
        }
        $this->toBeVisited["{$this->baseUrl}/{$page}"] = false;
        $this->visited[] = strtolower("{$this->baseUrl}/{$page}");
        return true;
    }

    /**
     * Start scraping from collected product links
     * @param string $exportFile name of export file to export to
     * @return type
     */
    public function startScraping($exportFile = '')
    {
        if (empty($this->productLinks)) {
            echo 'No Urls To Scrape.', $this->newLine;
            return;
        }
        foreach ($this->productLinks as $i => $url) {
            if ($this->scrapeProduct($url, $i + 1)) {
                $this->exportProduct("./{$this->currentDIR}/{$exportFile}");
                $this->exportISBN("{$exportFile}_ISBN");
            }
        }
        echo $this->newLine, $this->newLine;
    }

    /**
     * Export last added product to file
     * @param string $exportFile
     * @return type
     */
    public function exportProduct($exportFile)
    {
        if (empty($exportFile) ||
            empty($this->scrapedProducts)
        ) {
            return;
        }
        try {
            $csv = $this->getFilePointer($exportFile);
            $last_index = count($this->scrapedProducts) - 1;
            fputcsv($csv, $this->scrapedProducts[$last_index]);
        } catch (Exception $ex) {
            echo "\t\tFailed to open Export File: {$exportFile}", $this->newLine;
            echo print_r($ex, 1);
            return;
        }
    }

    /**
     * Export list of failed links to scrape later
     * @param string $exportFile
     * @return type
     */
    public function exportFailedLinks($exportFile)
    {
        if (empty($exportFile) ||
            empty($this->failedLinks)
        ) {
            return;
        }
        $csv = $this->getFilePointer($exportFile);
        foreach ($this->failedLinks as $link) {
            fputcsv($csv, array($link));
        }
        fclose($csv);
    }

    /**
     * Download an image from a source
     * @param string $src
     * @return type
     */
    public function downloadImage($src)
    {
        if ($src !== $this->baseUrl) {
            // if not home page then remove base url because appended later
            $src = str_replace($this->baseUrl, '', $src);
        }
        if ($src[0] === '/') {
            $src = substr($src, 1);
        }
        echo "\t\tDownloading Image: {$src}", $this->newLine;
        try {
            $ch = curl_init("{$this->baseUrl}/{$src}");
            $img = fopen("./{$this->currentDIR}/images/{$this->productID}.jpg", 'wb');
            curl_setopt($ch, CURLOPT_FILE, $img);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_exec($ch);
            curl_close($ch);
            fclose($img);
        } catch (Exception $ex) {
            echo "\t\tFailed to download image from {$this->baseUrl}/{$src}", $this->newLine;
            echo print_r($ex, 1);
            return;
        }
    }

    /**
     * Scrape info from yet to be visited pages
     * @param $exportFile
     */
    public function scrapeRemainder($exportFile, $nonVisitedCount)
    {
        if (empty($nonVisitedCount) || $nonVisitedCount == 0) {
            return;
        }
        echo "SCRAPE PRODUCTS FROM => {$nonVisitedCount} PAGES", $this->newLine, $this->newLine;
        foreach ($this->toBeVisited as $href => $yetToVisit) {
            if ($yetToVisit) {
                $this->fetchProductUrls($href);
                $this->startScraping($exportFile);
            }
        }
        $this->scrapeRemainder($exportFile, array_sum(array_values($this->toBeVisited)));
    }

    /**
     * Save exported ISBNs in a file to avoid future duplication
     * @param string $exportFile
     * @return type
     */
    public function exportISBN($exportFile)
    {
        if (empty($exportFile) ||
            empty($this->bookISBNS)
        ) {
            return;
        }
        try {
            $csv = $this->getFilePointerISBN($exportFile);
            $last_index = count($this->bookISBNS) - 1;
            fputcsv($csv, array($this->bookISBNS[$last_index]));
        } catch (Exception $ex) {
            echo "\t\tFailed to open Export File: {$exportFile}", $this->newLine;
            echo print_r($ex, 1);
            return;
        }
    }

    /**
     * For use of child classes only
     * @param type $find string to search through dom
     */
    protected function loadProductLinks($find)
    {
        $links = $this->html->find($find);
        foreach ($links as $a) {
            // first verify its a valid site url
            if (strpos($a->href, $this->baseUrl) !== false &&
                !in_array($a->href, $this->productLinks) &&
                (!isset($this->toBeVisited[$a->href]) || $this->toBeVisited[$a->href] == false)
            ) {
                $this->productLinks[] = $a->href;
            }
        }
    }

    /**
     * For use of child classes only
     * @param type $find string to search through dom
     */
    protected function loadToBeVisited($find)
    {
        $links = $this->html->find($find);
        foreach ($links as $a) {
            // first verify its a valid site url
            if (strpos($a->href, $this->baseUrl) !== false &&
                !in_array($a->href, $this->productLinks) &&
                (!isset($this->toBeVisited[$a->href]) || $this->toBeVisited[$a->href] == false)
            ) {
                $this->toBeVisited[$a->href] = true;
            }
        }
    }
}
