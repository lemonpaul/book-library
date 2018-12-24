<?php

namespace App\Controller;

use App\Entity\Book;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Routing\Annotation\Route;

class BookController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index()
    {
    	$bookRepository = $this->getDoctrine()->getRepository(Book::class);

        $books = $bookRepository->findAll();

        return $this->render('book/index.html.twig', [
            'books' => $books,
        ]);
    }

    /**
     * @Route("/new", name="new")
     */
    public function new(Request $request)
    {
        $book = new Book();
        $book->setTitle('New book');
        $book->setAuthor('Author');
        $book->setDate(new \DateTime('today'));
        $book->setDownload(false);

        $form = $this->createFormBuilder($book)
            ->add('title', TextType::class)
            ->add('author', TextType::class)
            ->add('cover', FileType::class)
            ->add('file', FileType::class)
            ->add('date', DateType::class)
            ->add('download', CheckboxType::class)
            ->add('save', SubmitType::class, array('label' => 'Add Book'))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $book = $form->getData();
            $bookManager = $this->getDoctrine()->getManager();
            $bookManager->persist($book);
            $bookManager->flush();
            return $this->redirectToRoute('index');
        }

        return $this->render('book/new.html.twig', array(
            'form' => $form->createView()
        ));
    }
}
