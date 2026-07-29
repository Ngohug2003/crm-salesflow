<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ProductDemoController extends Controller
{
    public function index(): View
    {
        return view('products.index', [
            'products' => $this->products(),
        ]);
    }

    public function create(): View
    {
        return view('products.form', [
            'product' => null,
            'title' => 'Thêm sản phẩm',
            'submitRoute' => route('products.store'),
            'method' => 'POST',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->validateInput($request);

        return redirect()
            ->route('products.index')
            ->with('success', 'Đã kiểm tra luồng tạo sản phẩm. Dữ liệu demo không được lưu.');
    }

    public function show(int $productId): View
    {
        return view('products.show', [
            'product' => $this->findProduct($productId),
        ]);
    }

    public function edit(int $productId): View
    {
        $product = $this->findProduct($productId);

        return view('products.form', [
            'product' => $product,
            'title' => 'Chỉnh sửa sản phẩm',
            'submitRoute' => route('products.update', $productId),
            'method' => 'PUT',
        ]);
    }

    public function update(Request $request, int $productId): RedirectResponse
    {
        $this->findProduct($productId);
        $this->validateInput($request);

        return redirect()
            ->route('products.show', $productId)
            ->with('success', 'Đã kiểm tra luồng chỉnh sửa. Dữ liệu demo không được lưu.');
    }

    public function destroy(int $productId): RedirectResponse
    {
        $this->findProduct($productId);

        return redirect()
            ->route('products.index')
            ->with('success', 'Đã kiểm tra quyền xóa. Dữ liệu demo không bị thay đổi.');
    }

    /**
     * @return list<array{
     *     id: int,
     *     code: string,
     *     name: string,
     *     category: string,
     *     unit: string,
     *     price: int,
     *     status: string
     * }>
     */
    private function products(): array
    {
        return [
            [
                'id' => 1,
                'code' => 'SF-CRM-01',
                'name' => 'Gói SalesFlow CRM Standard',
                'category' => 'Phần mềm',
                'unit' => 'Tài khoản/tháng',
                'price' => 490000,
                'status' => 'Đang kinh doanh',
            ],
            [
                'id' => 2,
                'code' => 'SF-CRM-02',
                'name' => 'Gói SalesFlow CRM Enterprise',
                'category' => 'Phần mềm',
                'unit' => 'Tài khoản/tháng',
                'price' => 990000,
                'status' => 'Đang kinh doanh',
            ],
            [
                'id' => 3,
                'code' => 'SF-ONBOARD',
                'name' => 'Dịch vụ triển khai và đào tạo',
                'category' => 'Dịch vụ',
                'unit' => 'Gói',
                'price' => 15000000,
                'status' => 'Đang kinh doanh',
            ],
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     code: string,
     *     name: string,
     *     category: string,
     *     unit: string,
     *     price: int,
     *     status: string
     * }
     */
    private function findProduct(int $productId): array
    {
        foreach ($this->products() as $product) {
            if ($product['id'] === $productId) {
                return $product;
            }
        }

        abort(404);
    }

    private function validateInput(Request $request): void
    {
        $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'unit' => ['required', 'string', 'max:100'],
            'price' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
        ]);
    }
}
