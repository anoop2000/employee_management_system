<?php

namespace App\Controller;

use App\Entity\Employee;
use App\Form\EmployeeType;
use App\Repository\EmployeeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/employee')]
final class EmployeeController extends AbstractController
{
    // #[Route(name: 'app_employee_index', methods: ['GET'])]
    // public function index(EmployeeRepository $employeeRepository): Response
    // {
    //     return $this->render('employee/index.html.twig', [
    //         'employees' => $employeeRepository->findAll(),
    //     ]);
    // }

    #[Route('/new', name: 'app_employee_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $employee = new Employee();
        $form = $this->createForm(EmployeeType::class, $employee);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($employee);
            $entityManager->flush();

            return $this->redirectToRoute('app_employee_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('employee/new.html.twig', [
            'employee' => $employee,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_employee_show', methods: ['GET'])]
    public function show(Employee $employee): Response
    {
        return $this->render('employee/show.html.twig', [
            'employee' => $employee,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_employee_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Employee $employee, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EmployeeType::class, $employee);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_employee_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('employee/edit.html.twig', [
            'employee' => $employee,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_employee_delete', methods: ['POST'])]
    public function delete(Request $request, Employee $employee, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$employee->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($employee);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_employee_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route(name: 'app_employee_index', methods: ['GET'])]
     public function index(Request $request, EmployeeRepository $employeeRepository): Response
{
    $search = $request->query->get('search');
    $position = $request->query->get('position');
    $page = $request->query->getInt('page', 1);
    $limit = 5;

    $queryBuilder = $employeeRepository->createQueryBuilder('e');

    if ($search) {
        $queryBuilder
            ->andWhere('e.name LIKE :search OR e.email LIKE :search')
            ->setParameter('search', '%' . $search . '%');
    }

    if ($position) {
        $queryBuilder
            ->andWhere('e.position = :position')
            ->setParameter('position', $position);
    }

    $queryBuilder->orderBy('e.id', 'ASC');

    $query = $queryBuilder->getQuery();

    $totalEmployees = count($query->getResult());
    $pages = ceil($totalEmployees / $limit);

    $employees = $queryBuilder
        ->setFirstResult(($page - 1) * $limit)
        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();

    return $this->render('employee/index.html.twig', [
        'employees' => $employees,
        'current_page' => $page,
        'total_pages' => $pages,
        'search' => $search,
        'position' => $position,
    ]);
}
}
