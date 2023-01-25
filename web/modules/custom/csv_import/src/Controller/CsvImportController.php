<?php
namespace Drupal\csv_import\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\asset\Entity\Asset;
/**
 * Provides route responses for the Example module.
 */
class CsvImportController extends ControllerBase {

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function test() {
    return [
      '#children' => '
        inputs:
        <form action="/csv_import/process" enctype="multipart/form-data" method="post">
          <input type="file" id="file" name="file">
          <input type="submit">
        </form>
        
        operations:
        <form action="/csv_import/process2" enctype="multipart/form-data" method="post">
          <input type="file" id="file" name="file">
          <input type="submit">
        </form>
    ',
    ];
  }


  public function process() {
    $file = \Drupal::request()->files->get("file");
    $fName = $file->getClientOriginalName();
    $fLoc = $file->getRealPath();
    $csv = array_map('str_getcsv', file($fLoc));


    $operation = \Drupal::entityTypeManager()->getStorage('asset')->load($csv[1][0]);
    $project = \Drupal::entityTypeManager()->getStorage('asset')->load($operation->get('project')->target_id);

    $input_submission = [];
    $input_submission['type'] = 'input';
    $input_submission['field_input_date'] = strtotime($csv[1][1]);
    $input_submission['field_input_category'] = $csv[1][2];
    $input_submission['field_input'] = $csv[1][3];
    $input_submission['field_unit'] = $csv[1][4];
    $input_submission['field_rate_units'] = $csv[1][5];
    $input_submission['field_cost_per_unit'] = $csv[1][6];
    $input_submission['field_custom_application_unit'] = $csv[1][7];
    $input_submission['project'] = $project;

    $operation_taxonomy_name = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($operation->get('field_operation')->target_id);
    $input_taxonomy_name = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($csv[1][2]);
    $input_submission['name'] = $operation_taxonomy_name->getName() . "_" . $input_taxonomy_name->getName() . "_" . $csv[1][1];

    
    $input_to_save = Asset::create($input_submission);

    $cost_submission = [];
    $cost_submission ['type'] = 'cost_sequence';
    $cost_submission ['field_cost_type'] = $csv[1][8];
    $cost_submission ['field_cost'] = $csv[1][9];

    $other_cost = Asset::create($cost_submission);

    $input_to_save->set('field_input_cost_sequences', $other_cost);
    $input_to_save->save();
    
    $operation->get('field_input')[] = $input_to_save->id();
    $operation->save();

    return [
      "#children" => "uploaded.",
    ];
    
  }

  public function process2() {
    $file = \Drupal::request()->files->get("file");
    $fName = $file->getClientOriginalName();
    $fLoc = $file->getRealPath();
    $csv = array_map('str_getcsv', file($fLoc));

    $shmu = \Drupal::entityTypeManager()->getStorage('asset')->load($csv[1][0]);
    $project = \Drupal::entityTypeManager()->getStorage('asset')->load($shmu->get('project')->target_id);

    $field_input = \Drupal::entityTypeManager()->getStorage('asset')->load($csv[1][2]);


    $operation_submission = [];
    $operation_submission['type'] = 'operation';

    $operation_submission['shmu'] = $shmu;
    $operation_submission['field_operation_date'] = strtotime($csv[1][1]);
    //$operation_submission['field_input'] = $field_input;
    $operation_submission['field_operation'] = $csv[1][3];
    $operation_submission['field_ownership_status'] = $csv[1][4];
    $operation_submission['field_tractor_self_propelled_machine'] = $csv[1][5];
    $operation_submission['field_row_number'] = $csv[1][6];
    $operation_submission['field_width'] = $csv[1][7];
    $operation_submissionoperation_submission['field_horsepower'] = $csv[1][8];
    $operation_submission['project'] = $project;

    $operation_to_save = Asset::create($operation_submission);

    // $cost_submission = [];
    // $cost_submission ['type'] = 'cost_sequence';
    // $cost_submission ['field_cost_type'] = $csv[1][9];
    // $cost_submission ['field_cost'] = $csv[1][8];

    // $other_cost = Asset::create($cost_submission);

    // $operation_to_save->set('field_operation_cost_sequences', $other_cost);
    
    $operation_to_save->save();

    return [
      "#children" => nl2br(print_r("saved", true)),
    ];
    
  }

}