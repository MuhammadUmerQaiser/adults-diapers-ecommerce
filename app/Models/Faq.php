<?php

namespace App\Models;

use App\Http\Resources\FaqResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Faq extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function getFaqs(Request $request)
    {
        try {
            $faqs = Faq::when(!$request->show_all, fn($q) => $q->where('is_active', true))
                ->orderBy('sort_order')
                ->orderBy('created_at')
                ->get();

            return api_success(FaqResource::collection($faqs), 'FAQs retrieved successfully.');

        } catch (\Exception $e) {
            return api_error('Something went wrong while retrieving FAQs.', 500, $e->getMessage());
        }
    }

    public function addFaq(Request $request)
    {
        try {
            $faq = Faq::create($request->validated());

            return api_success(new FaqResource($faq), 'FAQ created successfully.', 201);

        } catch (\Exception $e) {
            return api_error('Something went wrong while creating FAQ.', 500, $e->getMessage());
        }
    }

    public function updateFaq(Request $request, int $id)
    {
        try {
            $faq = Faq::findOrFail($id);
            $faq->update($request->validated());

            return api_success(new FaqResource($faq), 'FAQ updated successfully.');

        } catch (\Exception $e) {
            return api_error('FAQ not found or could not be updated.', 404, $e->getMessage());
        }
    }

    public function deleteFaq(int $id)
    {
        try {
            Faq::findOrFail($id)->delete();

            return api_success(null, 'FAQ deleted successfully.');

        } catch (\Exception $e) {
            return api_error('FAQ not found.', 404, $e->getMessage());
        }
    }
}
