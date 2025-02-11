<?php

namespace Drupal\task_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

class TaskManagerController extends ControllerBase {

  public function listAll(Request $request) {
    $schoolStudents = \Drupal::entityTypeManager()->getStorage('school_students');
    $param_value = $request->query->get('search', '');
    $ids = $schoolStudents->getQuery()
      ->accessCheck(TRUE) // or FALSE
      ->condition('field_student_name', "%$param_value%",'LIKE')
      ->execute();
      ini_set("memory_limit",-1);
    $students = $schoolStudents->loadMultiple($ids);
    $i =0;
    $studentsPaymentList = array();
    $termPayments = array();
    $classDetails = array();
    foreach ($students as $student){
      //print_r($student);exit;
      $studentsPaymentList[$i]['studentname'] = $field_student_name = $student->get('field_student_name')->value;
      $class_id = $student->get('field_class')->target_id;
      $classDetails = $this->loadClassDetail($class_id);
      $studentsPaymentList[$i]['classname'] = $classDetails['name'];
      $studentsPaymentList[$i]['totalFees'] = $classDetails['fees'];
      $termPayments = $this->loadStudentPayment($student->id());
      $studentsPaymentList[$i]['term1'] = (isset($termPayments['term1']))?$termPayments['term1']:0;
      $studentsPaymentList[$i]['term2'] = (isset($termPayments['term2']))?$termPayments['term2']:0;
      $totalPaid = $studentsPaymentList[$i]['term1'] + $studentsPaymentList[$i]['term2'];
      $studentsPaymentList[$i]['balanceFee'] = $studentsPaymentList[$i]['totalFees'] - $totalPaid;
      $i++;
      #echo "student Id---".$student->id()."====";          
      #print_r($field_student_name.$class_id);
    }
    $filterForm = $this->filterForm();
    return [
    '#theme' => 'payment_list',
    '#studentList' => $studentsPaymentList,
    '#form' =>  $filterForm,
    ];
  }


  public function loadClassDetail($id) {
    $schoolClasses = \Drupal::entityTypeManager()->getStorage('classes');
    $ids = $schoolClasses->getQuery()
      ->accessCheck(TRUE)
      ->condition('id', $id) // or FALSE
      ->execute();

    $classes = $schoolClasses->loadMultiple($ids);
    foreach ($classes as $class) {    
      $classDetails['name'] = $class->get('field_class_name')->value;
      $classDetails['fees'] = $class->get('field_fees')->value;
    }
    return $classDetails;
  }

  public function loadStudentPayment($student_id) {
    $schoolPayments = \Drupal::entityTypeManager()->getStorage('school_payment');
    $ids = $schoolPayments->getQuery()
    ->accessCheck(TRUE)
    ->condition('field_student_name', $student_id) // or FALSE
    ->execute();
    $termPayments = array();
    $payments = $schoolPayments->loadMultiple($ids);
    foreach ($payments as $payment) {
      #echo "from paymentDetail";
      #print_r($payment->get('field_term_1')->value);    
      $termPayments['term1'] = $payment->get('field_term_1')->value;
      $termPayments['term2'] = $payment->get('field_term_2')->value;
    }
    return $termPayments;
  }

  public function filterForm() {
    $form = \Drupal::formBuilder()->getForm('Drupal\task_manager\Form\FilterForm');
    return $form;
  }

}