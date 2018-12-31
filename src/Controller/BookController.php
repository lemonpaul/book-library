<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use App\Serializer\BookNormalizer;
use App\Entity\Book;
use App\Form\AddBookType;
use App\Form\EditBookType;

class BookController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index()
    {
        $cache = new FilesystemCache();
        if (!$cache->has('books.all')) {
    	    $bookRepository = $this->getDoctrine()->getRepository(Book::class);
            $cache->set('books.all', $bookRepository->findAll());
        }

        $books = $cache->get('books.all');

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
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $cache = new FilesystemCache();
        $book = $this->getDoctrine()
            ->getRepository(Book::class)
            ->find($id);
        $bookManager = $this->getDoctrine()->getManager();
        $bookManager->remove($book);
        $bookManager->flush();
        $cache->delete('books.all');
        return $this->redirectToRoute('index');
    }

    /**
     * @Route("/add", name="add")
     */
    public function add(Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $cache = new FilesystemCache();
        $book = new Book();
        $book->setDate(new \DateTime('today'));

        $form = $this->createForm(AddBookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cover = $form->get('cover')->getData();
            if ($cover)
            {
                $coverName = md5(uniqid());
                if ($cover->guessExtension()) {
                    $coverName .= '.'.$cover->guessExtension();
                }
                $cover->move("uploads/covers", $coverName);
                $book->setCover($coverName);
            }
            $file = $form->get('file')->getData();
            if ($file) {
                $fileName = md5(uniqid());
                if ($file->guessExtension()) {
                    $fileName .= '.'.$file->guessExtension();
                }
                $file->move("uploads/files", $fileName);
                $book->setFile($fileName);
            }
            $bookManager = $this->getDoctrine()->getManager();
            $bookManager->persist($book);
            $bookManager->flush();
            $cache->delete('books.all');
            return $this->redirectToRoute('index');
        }

        return $this->render('book/add.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/edit/{id}", name="edit")
     */
    public function edit($id, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $cache = new FilesystemCache();
        $book = $this->getDoctrine()
            ->getRepository(Book::class)
            ->find($id);

        $form = $this->createForm(EditBookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('delete_cover')->isClicked()) {
                $cover = $book->getCover();
                if ($cover) {
                    unlink("uploads/covers/".$cover);
                }
                $book->setCover('');
                $bookManager = $this->getDoctrine()->getManager();
                $bookManager->flush();
                $cache->delete('books.all');
                return $this->render('book/edit.html.twig', array(
                    'form' => $form->createView(),
                    'book' => $book
                ));
            } elseif ($form->get('delete_file')->isClicked()) {
                $file = $book->getFile();
                if ($file) {
                    unlink("uploads/files/".$file);
                }
                $book->setFile('');
                $book->setDownload(false);
                $bookManager = $this->getDoctrine()->getManager();
                $bookManager->flush();
                $cache->delete('books.all');
                return $this->render('book/edit.html.twig', array(
                    'form' => $form->createView(),
                    'book' => $book
                ));
            } else {
                $bookManager = $this->getDoctrine()->getManager();
                $bookManager->flush();
                $cache->delete('books.all');
                return $this->redirectToRoute('index');
            }
        }

        return $this->render('book/edit.html.twig', array(
            'form' => $form->createView(),
            'book' => $book
        ));
    }

    /**
     * @Route("/api/v1/books", name="api_index")
     */
    public function api_index(Request $request)
    {
        $apiKey = $this->getParameter('api_key');

        if ($request->query->get('api_key') !== $apiKey) {
            return JsonResponse::fromJsonString('{"error": "AccessDenied"}');
        }

        $encoders = array(new JsonEncoder());
        $normalizers = array(new BookNormalizer());

        $serializer = new Serializer($normalizers, $encoders);

        $cache = new FilesystemCache();
        if (!$cache->has('books.all')) {
    	    $bookRepository = $this->getDoctrine()->getRepository(Book::class);
            $cache->set('books.all', $bookRepository->findAll());
        }

        $books = $cache->get('books.all');

        $jsonContent = $serializer->serialize($books, 'json');

        return JsonResponse::fromJsonString($jsonContent);
    }

    /**
     * @Route("/api/v1/books/{id}/edit", name="api_edit")
     */
    public function api_edit($id, Request $request)
    {
        $apiKey = $this->getParameter('api_key');

        if ($request->query->get('api_key') !== $apiKey) {
            return JsonResponse::fromJsonString('{"error": "AccessDenied"}');
        }

        $cache = new FilesystemCache();
        $book = $this->getDoctrine()
            ->getRepository(Book::class)
            ->find($id);

        if ($book) {
            $title = $request->query->get('title');
            if ($title) {
                $book->setTitle($title);
                $cache->delete('books.all');
            }

            $author = $request->query->get('author');
            if ($author) {
                $book->setAuthor($author);
                $cache->delete('books.all');
            }

            $download = $request->query->get('download');
            if (null !== $download) {
                if ($download == "true") {
                    $book->setDownload(true);
                } elseif ($download == "false") {
                    $book->setDownload(false);
                }
                $cache->delete('books.all');
            }

            $date = $request->query->get('date');
            if ($date) {
                $book->setDate(new \DateTime($date));
                $cache->delete('books.all');
            }
        }

        $bookManager = $this->getDoctrine()->getManager();
        $bookManager->flush();

        if (!$cache->has('books.all')) {
    	    $bookRepository = $this->getDoctrine()->getRepository(Book::class);
            $cache->set('books.all', $bookRepository->findAll());
        }

        $books = $cache->get('books.all');

        $encoders = array(new JsonEncoder());
        $normalizers = array(new BookNormalizer());

        $serializer = new Serializer($normalizers, $encoders);

        $jsonContent = $serializer->serialize($books, 'json');

        return JsonResponse::fromJsonString($jsonContent);
    }

    /**
     * @Route("/api/v1/books/add", name="api_add")
     */
    public function api_add(Request $request)
    {
        $apiKey = $this->getParameter('api_key');

        if ($request->query->get('api_key') !== $apiKey) {
            return JsonResponse::fromJsonString('{"error": "AccessDenied"}');
        }

        $cache = new FilesystemCache();

        $book = new Book();
        $book->setTitle("Title");
        $book->setAuthor("Author");
        $book->setDate(new \DateTime('today'));
        $book->setDownload(false);

        $title = $request->query->get('title');
        if ($title) {
            $book->setTitle($title);
            $cache->delete('books.all');
        }

        $author = $request->query->get('author');
        if ($author) {
            $book->setAuthor($author);
            $cache->delete('books.all');
        }

        $download = $request->query->get('download');
        if (null !== $download) {
            if ($download == "true") {
                $book->setDownload(true);
            } elseif ($download == "false") {
                $book->setDownload(false);
            }
            $cache->delete('books.all');
        }

        $date = $request->query->get('date');
        if ($date) {
            $book->setDate(new \DateTime($date));
            $cache->delete('books.all');
        }

        $bookManager = $this->getDoctrine()->getManager();
        $bookManager->persist($book);
        $bookManager->flush();

        if (!$cache->has('books.all')) {
    	    $bookRepository = $this->getDoctrine()->getRepository(Book::class);
            $cache->set('books.all', $bookRepository->findAll());
        }

        $books = $cache->get('books.all');

        $encoders = array(new JsonEncoder());
        $normalizers = array(new BookNormalizer());

        $serializer = new Serializer($normalizers, $encoders);

        $jsonContent = $serializer->serialize($books, 'json');

        return JsonResponse::fromJsonString($jsonContent);
    }
}
