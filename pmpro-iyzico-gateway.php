<?php
/*
Plugin Name: Iyzico Gateway for Paid Memberships Pro
Description: Iyzico Gateway for Paid Memberships Pro
Version: 1.2
Author: FTI Technologies
Author URI: https://www.freelancetoindia.com/

*/

define("PMPRO_IYZICOGATEWAY_DIR", dirname(__FILE__));

//load payment gateway class

require_once(PMPRO_IYZICOGATEWAY_DIR . "/classes/class.pmprogateway_iyzico.php");