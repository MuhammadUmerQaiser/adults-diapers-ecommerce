<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Global\FaqRequest;
use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    protected $faq;

    public function __construct(Faq $faq)
    {
        $this->faq = $faq;
    }

    public function getFaqs(Request $request)
    {
        return $this->faq->getFaqs($request);
    }

    
    public function addFaq(FaqRequest $request)
    {
        return $this->faq->addFaq($request);
    }

    public function updateFaq(FaqRequest $request, int $id)
    {
        return $this->faq->updateFaq($request, $id);
    }

    public function deleteFaq(int $id)
    {
        return $this->faq->deleteFaq($id);
    }
}
