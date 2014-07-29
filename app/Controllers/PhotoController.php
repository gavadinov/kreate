<?php

class PhotoController
{
    public function index()
    {
        return 'index';
    }

    public function show($id)
    {
        return 'show ' . $id;
    }

    public function edit($id)
    {
        return 'edit ' . $id;
    }

    public function create()
    {
        return 'create';
    }
}
