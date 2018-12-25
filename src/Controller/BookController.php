<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Book;
use App\Form\BookType;

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
     * @Route("/view/{id}", name="view")
     */
    public function view($id)
    {
        $book = $this->getDoctrine()
            ->getRepository(Book::class)
            ->find($id);
        return $this->render('book/view.html.twig', [
            'book' => $book,
        ]);
    }

    /**
     * @Route("/delete/{id}", name="delete")
     */
    public function delete($id)
    {
        $book = $this->getDoctrine()
            ->getRepository(Book::class)
            ->find($id);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($book);
        $entityManager->flush();
        return $this->redirectToRoute('index');
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

        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cover = $form->get('cover')->getData();
            $coverName = md5(uniqid());
            if ($cover->guessExtension()) {
                $coverName .= '.'.$cover->guessExtension();
            }
            $cover->move("%kernel.project_dir%/public/uploads/covers", $coverName);
            $book->setCover($coverName);
            $file = $form->get('file')->getData();
            if ($book->getDownload() && $file) {
                $fileName = md5(uniqid());
                if ($file->guessExtension()) {
                    $fileName .= '.'.$file->guessExtension();
                }
                $file->move("%kernel.project_dir%/public/uploads/files", $fileName);
                $book->setFile($fileName);
            }
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($book);
            $entityManager->flush();
            return $this->redirectToRoute('index');
        }

        return $this->render('book/new.html.twig', array(
            'form' => $form->createView()
        ));
    }
}
