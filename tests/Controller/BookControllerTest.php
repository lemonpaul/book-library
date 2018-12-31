<?php

namespace App\Tests\Controller;

use App\Entity\Book;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

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
}
