<?php

class CRM_Caseemail_CivirulesAction_Send extends CRM_Emailapi_CivirulesAction_Send {

  /**
   * Returns an array with parameters used for processing an action
   *
   * @param array $parameters
   * @param CRM_Civirules_TriggerData_TriggerData $rtiggerData
   * @return array
   * @access protected
   */
  protected function alterApiParameters($parameters, CRM_Civirules_TriggerData_TriggerData $triggerData) {
    //this method could be overridden in subclasses to alter parameters to meet certain criteria
    $caseRoles = $parameters['case_roles_select'];
    $to = $this->getEmailFromCaseRoles($caseRoles);
    $parameters['contact_id'] = array('IN' => $to);
    if (!empty($actionParameters['cc'])) {
      $parameters['cc'] = $actionParameters['cc'];
    }
    if (!empty($actionParameters['bcc'])) {
      $parameters['bcc'] = $actionParameters['bcc'];
    }
    return $parameters;
  }

  /**
   * Returns a redirect url to extra data input from the user after adding a action
   *
   * Return false if you do not need extra data input
   *
   * @param int $ruleActionId
   * @return bool|string
   * $access public
   */
  public function getExtraDataInputUrl($ruleActionId) {
    return CRM_Utils_System::url('civicrm/civirules/actions/caseemail', 'rule_action_id='.$ruleActionId);
  }

  /**
   * Returns a user friendly text explaining the condition params
   * e.g. 'Older than 65'
   *
   * @return string
   * @access public
   */
  public function userFriendlyConditionParams() {
    $template = 'unknown template';
    $params = $this->getActionParameters();
    $version = CRM_Core_BAO_Domain::version();
    // Compatibility with CiviCRM > 4.3
    if($version >= 4.4) {
      $messageTemplates = new CRM_Core_DAO_MessageTemplate();
    } else {
      $messageTemplates = new CRM_Core_DAO_MessageTemplates();
    }
    $messageTemplates->id = $params['template_id'];
    $messageTemplates->is_active = true;
    if ($messageTemplates->find(TRUE)) {
      $template = $messageTemplates->msg_title;
    }
    if (isset($params['location_type_id']) && !empty($params['location_type_id'])) {
      try {
        $locationText = 'location type ' . civicrm_api3('LocationType', 'getvalue', array(
            'return' => 'display_name',
            'id' => $params['location_type_id'],
          )) . ' with primary e-mailaddress as fall back';
      }
      catch (CiviCRM_API3_Exception $ex) {
        $locationText = 'location type ' . $params['location_type_id'];
      }
    }
    else {
      $locationText = "primary e-mailaddress";
    }
    $caseRoles = $params['case_roles_select'];
    $to = $this->getEmailFromCaseRoles($caseRoles);
    $to = 'contact ids: ' . implode('; ', $to);
    $cc = "";
    if (!empty($params['cc'])) {
      $cc = ts(' and cc to %1', array(1=>$params['cc']));
    }
    $bcc = "";
    if (!empty($params['bcc'])) {
      $bcc = ts(' and bcc to %1', array(1=>$params['bcc']));
    }
    return ts('Send e-mail from "%1 (%2 using %3)" with Template "%4" to %5 %6 %7', array(
      1=>$params['from_name'],
      2=>$params['from_email'],
      3=>$locationText,
      4=>$template,
      5 => $to,
      6 => $cc,
      7 => $bcc
    ));
  }

  protected function getEmailFromCaseRoles($caseRoles = array()) {
    $to = array();
    $result = civicrm_api3('Relationship', 'get', array(
      'sequential' => 1,
      'relationship_type_id' => array('IN' => $caseRoles),
    ))['values'];
    foreach ($result as $rel) {
      $to[] = $rel['contact_id_b'];
    }
    drupal_set_message('<pre>cr result: '.print_r($result, 1).'</pre>');
    return $to;
  }
}