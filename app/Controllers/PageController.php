<?php
namespace App\Controllers;

use App\Core\Controller;

class PageController extends Controller {
  public function home(): void {
    require __DIR__ . '/../Views/index.php';
  }
}
