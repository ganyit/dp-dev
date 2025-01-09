<?php

namespace Drupal\task_manager\Controller;

use Drupal\Core\Controller\ControllerBase;

class TaskManagerController extends ControllerBase {

  public function listAll() {
    $schoolStudents = \Drupal::entityTypeManager()->getStorage('school_students');
    $ids = $schoolStudents->getQuery()
      ->accessCheck(TRUE) // or FALSE
      ->execute();
      ini_set("memory_limit",-1);
    $students = $schoolStudents->loadMultiple($ids);
    #echo '<pre>#########################################################123';
    #print_r($students);
    $i =0;
    foreach ($students as $student){
      //print_r($student);exit;
      $studentsPaymentList[$i]['studentname'] = $field_student_name = $student->get('field_student_name')->value;
      $class_id = $student->get('field_class')->target_id;
      $classDetails = $this->loadClassDetail($class_id);
      $studentsPaymentList[$i]['classname'] = $classDetails['name'];
      $studentsPaymentList[$i]['totalFees'] = $classDetails['fees'];
      $termPayments = $this->loadStudentPayment($student->id());
      $studentsPaymentList[$i]['term1'] = $termPayments['term1'];
      $studentsPaymentList[$i]['term2'] = $termPayments['term2'];
      $totalPaid = $termPayments['term1'] + $termPayments['term2'];
      $studentsPaymentList[$i]['balanceFee'] = $studentsPaymentList[$i]['totalFees'] - $totalPaid;
      $i++;
      #echo "student Id---".$student->id()."====";          
      #print_r($field_student_name.$class_id);
    }
    return [
    '#theme' => 'payment_list',
    '#studentList' => $studentsPaymentList,
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

    $payments = $schoolPayments->loadMultiple($ids);
    foreach ($payments as $payment) {
      #echo "from paymentDetail";
      #print_r($payment->get('field_term_1')->value);    
      $termPayments['term1'] = $payment->get('field_term_1')->value;
      $termPayments['term2'] = $payment->get('field_term_2')->value;
    }
    return $termPayments;
  }

}