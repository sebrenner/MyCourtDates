<?php 

function getJudge(&$caseNumber){
	return 'judgeUnkown';
}

//	URI getters
function getSumURI(&$caseNumber){
	return 'http://www.courtclerk.org/case_summary.asp?casenumber=' . rawurlencode($caseNumber);
}
function getDocsURI(&$caseNumber){
	return 'http://www.courtclerk.org/case_summary.asp?sec=doc&casenumber=' . rawurlencode($caseNumber);
}
function getCaseSchedURI(&$caseNumber){
	return 'http://www.courtclerk.org/case_summary.asp?sec=sched&casenumber=' . rawurlencode($caseNumber);
}
function getHistURI(&$caseNumber){
	return 'http://www.courtclerk.org/case_summary.asp?sec=history&casenumber=' . rawurlencode($caseNumber);
}

?>