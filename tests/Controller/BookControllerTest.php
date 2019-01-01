<?php

namespace App\Tests\Controller;

use App\Entity\Book;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Symfony\Component\Cache\Simple\FilesystemCache;

class BookControllerTest extends WebTestCase
{
    public function testUnathorizationalAddBook()
    {
        $client = static::createClient();

        $client->request('GET', '/add');

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }

    public function testAddBook()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/login');
        
        $form = $crawler->selectButton('Sign in')->form();

        $form['username'] = 'admin';
        $form['password'] = 'admin';

        $crawler = $client->submit($form);

        $crawler = $client->request('GET', '/add');

        $form = $crawler->selectButton('Add Book')->form();

        $form['add_book[title]'] = 'Title';
        $form['add_book[author]'] = 'Author';

        $client->followRedirects();
        $crawler = $client->submit($form);

        $this->assertContains('Title', $client->getResponse()->getContent());
        $this->assertContains('Author', $client->getResponse()->getContent());
        
        $crawler = $client->clickLink("Delete");
    }

    public function testApiAddBook()
    {
        $client = self::createClient();
        $kernel = self::bootKernel();

        $title = md5(uniqid());
        $author = md5(uniqid());
        $date = new \DateTime('today');
        $apiDate = $date->format('d.m.Y');
        $formatDate = $date->format('d/m/Y');
        
        $client->request('GET', '/api/v1/books/add?api_key='.$kernel->getContainer()
                                                                    ->getParameter('api_key')
                                                            .'&title='.$title
                                                            .'&author='.$author
                                                            .'&date='.$apiDate);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $jsonBooks = $client->getResponse()->getContent();
        $books = json_decode($jsonBooks, true);

        $isAdded = false;
        foreach ($books as $book) {
            if ($book['title'] == $title && $book['author'] == $author
                                         && $book['date'] == $formatDate) {
                $isAdded = true;
                $id = $book['id'];
                break;
            }
        }
        if ($isAdded) {
            $cache = new FilesystemCache();
            $bookManager = $kernel->getContainer()
                                  ->get('doctrine')
                                  ->getManager();
            $book = $bookManager->getRepository(Book::class)
                                ->find($id);
            $bookManager->remove($book);
            $bookManager->flush();
            $cache->delete('books.all');
        }
        $this->assertEquals(true, $isAdded);
    }
}
