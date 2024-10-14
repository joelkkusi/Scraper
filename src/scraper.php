<?php

require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

$base_uri = 'https://books.toscrape.com/';

$client = new Client([
    'base_uri' => $base_uri,
    // 'timeout'  => 2.0,
]);

// Scrape the available categories
function scrapeCategories(Client $client): array
{
    $response = $client->request('GET', '/');

    $htmlContent = $response->getBody()->getContents();
    $crawler = new Crawler($htmlContent);

    $categories = [];

    $crawler->filter('.nav li ul li a')->each(function (Crawler $node) use (&$categories) {
        $categoryName = $node->text();
        $categoryUrl = $node->attr('href');

        $categories[$categoryName] = $categoryUrl;
    });

    return $categories;
}

echo "Available Categories:\n";
$categories = scrapeCategories($client, $base_uri);
foreach (array_keys($categories) as $index => $categoryName) {
    echo "[" . ($index + 1) . "] $categoryName\n";
}

// Prompt the user to select a category
echo "Enter the number of the category you want to scrape: ";
$selectedCategoryIndex = (int) fgets(STDIN);

$selectedCategoryUrl = array_values($categories)[$selectedCategoryIndex - 1];

echo "Selected Category: $selectedCategoryUrl\n";

// Scrape the books in the selected category
function scrapeBooksInCategory(Client $client, $categoryUrl): array
{
    $response = $client->request('GET', $categoryUrl);

    $htmlContent = $response->getBody()->getContents();
    $crawler = new Crawler($htmlContent);

    $books = [];

    $crawler->filter('.product_pod h3 a')->each(function (Crawler $node) use (&$books) {
        $bookTitle = $node->text();
        $bookUrl = $node->attr('href');

        $books[$bookTitle] = $bookUrl;
    });

    return $books;
}

$books = scrapeBooksInCategory($client, $base_uri, $selectedCategoryUrl);

system('clear');

echo "Books in the selected category:\n";
foreach (array_keys($books) as $index => $bookTitle) {
    echo "[" . ($index + 1) . "] $bookTitle\n";
}

// Prompt the user to select a book
echo "Enter the number of the book you want to scrape: ";

$selectedBookIndex = (int) fgets(STDIN);
$selectedBookUrl = array_values($books)[$selectedBookIndex - 1];

system('clear');

echo "Selected Book: $selectedBookUrl\n";

// Scrape the book details
function scrapeBookDetails(Client $client, $base_uri, $bookUrl): array
{
    $categoryUrl = $base_uri . $bookUrl;
    $response = $client->request('GET',  $bookUrl);

    $htmlContent = $response->getBody()->getContents();
    $crawler = new Crawler($htmlContent);

    $bookDetails = [];

    $bookDetails['title'] = $crawler->filter('.product_main h1')->text();
    $bookDetails['price'] = $crawler->filter('.price_color')->text();
    $bookDetails['stars'] = trim($crawler->filter('.star-rating')->attr('class'), 'star-rating ');
    $bookDetails['availability'] = $crawler->filter('.instock.availability')->text();
    $bookDetails['description'] = $crawler->filter('#product_description')->nextAll()->text();

    return $bookDetails;
}

$bookDetails = scrapeBookDetails($client, $base_uri, $selectedBookUrl);

echo "Book Details:\n";
echo "Title: " . $bookDetails['title'] . "\n";
echo "Price: " . $bookDetails['price'] . "\n";
echo "Stars: " . $bookDetails['stars'] . " out of the Five" . "\n";
echo "Availability: " . $bookDetails['availability'] . "\n";
echo "Description: " . $bookDetails['description'] . "\n";
echo "URL: " . $base_uri . $selectedBookUrl . "\n";

// Exit the script
exit(0);