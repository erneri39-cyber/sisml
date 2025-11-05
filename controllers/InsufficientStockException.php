<?php

require_once __DIR__ . '/BusinessLogicException.php';

/**
 * Excepción lanzada cuando el stock de un producto es insuficiente para completar una venta.
 */
class InsufficientStockException extends BusinessLogicException {}