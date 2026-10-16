<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Controller, Database, Request};
use App\Core\Auth;
use App\Core\HttpError;
use App\Repositories\CategoryRepository;
use App\Repositories\MerchantRepository;
use App\Repositories\CouponRepository;
use App\Repositories\SavedCouponRepository;
use App\Services\CouponService;

final class CategoryController extends Controller
{
    public function index(Request $request): void
    {
        $db=Database::connection();
        $categories=(new CategoryRepository($db))->publicCounts();
        $selectedCategory=$request->query('category','0');
        if (!ctype_digit($selectedCategory)) throw new HttpError(422,'Danh mục không hợp lệ.');
        $selectedCategory=(int)$selectedCategory;
        if ($selectedCategory>0 && !in_array($selectedCategory,array_map('intval',array_column($categories,'id')),true)) throw new HttpError(404,'Danh mục không còn hoạt động.');
        $service=new CouponService(new CouponRepository($db));
        $filters=$service->normalizeSearch(['category_id'=>$selectedCategory,'page_size'=>3]);
        $filters['page_size']=3;
        $categoryCoupons=$service->search($filters);
        $currentUser=Auth::user();
        $savedCouponIds=$currentUser ? (new SavedCouponRepository($db))->savedIds((int)$currentUser['id'],array_column($categoryCoupons,'id')) : [];
        $this->view('categories/index', [
            'pageTitle'=>'Danh mục ưu đãi',
            'categories'=>$categories,
            'selectedCategory'=>$selectedCategory,
            'categoryCoupons'=>$categoryCoupons,
            'currentUser'=>$currentUser,
            'savedCouponIds'=>$savedCouponIds,
            'brandMerchants'=>array_map(static fn($merchant): array => ['id'=>$merchant->id,'name'=>$merchant->name],(new MerchantRepository(Database::connection()))->publicFilterOptions()),
        ]);
    }
}
