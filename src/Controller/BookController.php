<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;
use App\Entity\Book;
use App\Form\AddBookType;
use App\Form\EditBookType;
use App\Serializer\BookNormalizer;
use App\Service\CoverUploader;
use App\Service\FileUploader;

class BookController extends AbstractController
{
    /**
     * @Route("/", name="index")
     * 
     * @param Request $request
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(Request $request)
    {
        if ($request->query->get('page')) {
            $page = $request->query->get('page');
        } else {
            $page = 1;
        }
        $cache = new FilesystemCache();
        if (!$cache->has('books.all')) {
    	    $bookRepository = $this->getDoctrine()->getRepository(Book::class);
            $cache->set('books.all', $bookRepository->findAll(), 86400);
        }
        $books = $cache->get('books.all');
        return $this->render('book/index.html.twig', ['books' => $books, 'page' => $page, 'pages' => ceil(count($books)/5)]);
    }

    /**
     * @Route("/view/{id}", name="view")
     * 
     * @param integer $id
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function view($id)
    {
        $book = $this->getDoctrine()->getRepository(Book::class)->find($id);
        return $this->render('book/view.html.twig', ['book' => $book]);
    }

    /**
     * @Route("/delete/{id}", name="delete")
     * 
     * @param integer $id
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function delete($id)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $book = $this->getDoctrine()->getRepository(Book::class)->find($id);
        $bookManager = $this->getDoctrine()->getManager();
        $bookManager->remove($book);
        $bookManager->flush();
        $cache = new FilesystemCache();
        $cache->delete('books.all');
        return $this->redirectToRoute('index');
    }

    /**
     * @Route("/add", name="add")
     * 
     * @param Request $request
     * @param CoverUploader $coverUploader
     * @param FileUploader $fileUploader
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function add(Request $request, CoverUploader $coverUploader, FileUploader $fileUploader)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $book = new Book();
        $book->setDate(new \DateTime('today'));
        $form = $this->createForm(AddBookType::class, $book);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $cover = $form->get('cover')->getData();
            if ($cover) {
                $coverName = $coverUploader->upload($cover);
                $book->setCover($this->getParameter('covers_directory').'/'.$coverName);
            }
            $file = $form->get('file')->getData();
            if ($file) {
                $fileName = $fileUploader->upload($file);
                $book->setFile($this->getParameter('files_directory').'/'.$fileName);
            }
            $bookManager = $this->getDoctrine()->getManager();
            $bookManager->persist($book);
            $bookManager->flush();
            $cache = new FilesystemCache();
            $cache->delete('books.all');
            return $this->redirectToRoute('index');
        }
        return $this->render('book/add.html.twig', array('form' => $form->createView()));
    }

    /**
     * @Route("/edit/{id}", name="edit")
     * 
     * @param integer $id
     * @param Request $request
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function edit($id, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $book = $this->getDoctrine()->getRepository(Book::class)->find($id);
        $form = $this->createForm(EditBookType::class, $book);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('delete_cover')->isClicked()) {
                $cover = $book->getCover();
                if ($cover) {
                    unlink($cover);
                }
                $book->setCover('');
                $bookManager = $this->getDoctrine()->getManager();
                $bookManager->flush();
                $cache = new FilesystemCache();
                $cache->delete('books.all');
                return $this->render('book/edit.html.twig', array('form' => $form->createView(),
                                                                  'book' => $book));
            } elseif ($form->get('delete_file')->isClicked()) {
                $file = $book->getFile();
                if ($file) {
                    unlink($file);
                }
                $book->setFile('');
                $book->setDownload(false);
                $bookManager = $this->getDoctrine()->getManager();
                $bookManager->flush();
                $cache = new FilesystemCache();
                $cache->delete('books.all');
                return $this->render('book/edit.html.twig', array('form' => $form->createView(),
                                                                  'book' => $book));
            } else {
                $bookManager = $this->getDoctrine()->getManager();
                $bookManager->flush();
                $cache = new FilesystemCache();
                $cache->delete('books.all');
                return $this->redirectToRoute('index');
            }
        }
        return $this->render('book/edit.html.twig', array('form' => $form->createView(),
                                                          'book' => $book));
    }

    /**
     * @Route("/api/v1/books", name="apiIndex")
     * 
     * @param Request $request
     * 
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function apiIndex(Request $request)
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
            $cache->set('books.all', $bookRepository->findAll(), 86400);
        }
        $books = $cache->get('books.all');
        $jsonContent = $serializer->serialize($books, 'json');
        return JsonResponse::fromJsonString($jsonContent);
    }

    /**
     * @Route("/api/v1/books/{id}/edit", name="apiEdit")
     * 
     * @param integer $id
     * @param Request $request
     * 
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function apiEdit($id, Request $request)
    {
        $apiKey = $this->getParameter('api_key');
        if ($request->query->get('api_key') !== $apiKey) {
            return JsonResponse::fromJsonString('{"error": "AccessDenied"}');
        }
        $book = $this->getDoctrine()->getRepository(Book::class)->find($id);
        if ($book) {
            $title = $request->query->get('title');
            if ($title) {
                $book->setTitle($title);
            }
            $author = $request->query->get('author');
            if ($author) {
                $book->setAuthor($author);
            }
            $download = $request->query->get('download');
            if (null !== $download) {
                if ($download == "true") {
                    $book->setDownload(true);
                } elseif ($download == "false") {
                    $book->setDownload(false);
                }
            }
            $date = $request->query->get('date');
            if ($date) {
                $book->setDate(new \DateTime($date));
            }
        }
        $bookManager = $this->getDoctrine()->getManager();
        $bookManager->flush();
        $cache = new FilesystemCache();
    	$bookRepository = $this->getDoctrine()->getRepository(Book::class);
        $cache->set('books.all', $bookRepository->findAll(), 86400);
        $books = $cache->get('books.all');
        $encoders = array(new JsonEncoder());
        $normalizers = array(new BookNormalizer());
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($books, 'json');
        return JsonResponse::fromJsonString($jsonContent);
    }

    /**
     * @Route("/api/v1/books/add", name="apiAdd")
     * 
     * @param Request $request
     * 
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function apiAdd(Request $request)
    {
        $apiKey = $this->getParameter('api_key');
        if ($request->query->get('api_key') !== $apiKey) {
            return JsonResponse::fromJsonString('{"error": "AccessDenied"}');
        }
        $book = new Book();
        $book->setTitle("Title");
        $book->setAuthor("Author");
        $book->setDate(new \DateTime('today'));
        $book->setDownload(false);
        $title = $request->query->get('title');
        if ($title) {
            $book->setTitle($title);
        }
        $author = $request->query->get('author');
        if ($author) {
            $book->setAuthor($author);
        }
        $download = $request->query->get('download');
        if (null !== $download) {
            if ($download == "true") {
                $book->setDownload(true);
            } elseif ($download == "false") {
                $book->setDownload(false);
            }
        }
        $date = $request->query->get('date');
        if ($date) {
            $book->setDate(new \DateTime($date));
        }
        $bookManager = $this->getDoctrine()->getManager();
        $bookManager->persist($book);
        $bookManager->flush();
        $cache = new FilesystemCache();
    	$bookRepository = $this->getDoctrine()->getRepository(Book::class);
        $cache->set('books.all', $bookRepository->findAll(), 86400);
        $books = $cache->get('books.all');
        $encoders = array(new JsonEncoder());
        $normalizers = array(new BookNormalizer());
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($books, 'json');
        return JsonResponse::fromJsonString($jsonContent);
    }
}
