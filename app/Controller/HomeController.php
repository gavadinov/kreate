<?php
namespace Controller;

use Framework\Controller\AbstractController;
use Framework\Support\View;

class HomeController extends AbstractController
{
	public function index()
	{
		return View::make('home.index');
	}
}
