<?php

require_once __DIR__ . '/BusinessLogicException.php';

/**
 * Excepción lanzada cuando no se encuentra un registro esperado en la base de datos.
 */
class RecordNotFoundException extends BusinessLogicException {}