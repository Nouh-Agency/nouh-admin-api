<?php



namespace App\Services;

use App\Models\Category;

class CategoryService
{
  public function findAll()
  {
    return Category::with([
      'major',
      'projects' => fn($query) => $query->where('is_visible', true)->with('galleries.media'),
    ])->get();
  }

  public function createCategory(array $data)
  {
    $category = Category::create($data);
    return $category;
  }

  public function findOne(Category $category)
  {
    return $category->load([
      'major',
      'projects' => fn($query) => $query->where('is_visible', true)->with('galleries.media'),
    ]);
  }

  public function updateCategory(Category $category, array $data)
  {
    $category->update($data);
    return $category;
  }

  public function deleteCategory(Category $category)
  {
    return $category->delete();
  }
}
