<?php

class Controller_Welcome extends Controller {

	public function action_index()
	{
		$this->response->body(View::factory('welcome')->render());
	}

} // End Welcome
