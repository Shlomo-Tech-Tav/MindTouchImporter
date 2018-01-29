<?php
if (!function_exists('include_import_class')) {
	exit;
}
include_import_class('$import_type_file');
class $code extends $import_type_class {
}
