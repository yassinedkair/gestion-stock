<?php

namespace App\Http\Controllers;

use App\Models\CapitalOperation;
use App\Models\Charge;
use App\Models\Client;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Salary;
use App\Models\Sale;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestionController extends Controller
{
    public function index()
    {
        return view('gestion');
    }

    public function state(): JsonResponse
    {
        return response()->json($this->stateData());
    }

    private function stateData(): array
    {
        $products = Product::orderBy('name')->get();
        $clients = Client::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();
        $purchases = Purchase::with(['product', 'supplier'])->latest('date')->latest('id')->get();
        $sales = Sale::with(['product', 'client'])->latest('date')->latest('id')->get();
        $charges = Charge::latest('date')->latest('id')->get();
        $salaries = Salary::latest('date')->latest('id')->get();
        $capital = CapitalOperation::latest('date')->latest('id')->get();

        $totals = $this->totals();

        return compact('products', 'clients', 'suppliers', 'purchases', 'sales', 'charges', 'salaries', 'capital', 'totals');
    }

    private function totals(?array $exclude = null): array
    {
        $exclude = $exclude ?: [];
        $capital = CapitalOperation::query();
        $purchases = Purchase::query();
        $sales = Sale::query();
        $charges = Charge::query();
        $salaries = Salary::query();

        if (($exclude['purchase'] ?? null) !== null) $purchases->whereKeyNot($exclude['purchase']);
        if (($exclude['sale'] ?? null) !== null) $sales->whereKeyNot($exclude['sale']);
        if (($exclude['charge'] ?? null) !== null) $charges->whereKeyNot($exclude['charge']);
        if (($exclude['salary'] ?? null) !== null) $salaries->whereKeyNot($exclude['salary']);
        if (($exclude['capital'] ?? null) !== null) $capital->whereKeyNot($exclude['capital']);

        $ins = (float) $sales->sum('total') + (float) $capital->where('movement', true)->sum('amount');
        $outs = (float) $purchases->sum('total') + (float) $charges->sum('amount') + (float) $salaries->sum('amount');
        $result = $ins - $outs;
        $capitalValue = (float) $capital->sum('amount');
        $profit = $result - $capitalValue;

        return [
            'capital' => round($capitalValue, 2),
            'ins' => round($ins, 2),
            'outs' => round($outs, 2),
            'result' => round($result, 2),
            'profit' => round($profit, 2),
            'cash' => round($result, 2),
        ];
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['business' => $message]);
    }

    public function productStore(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'buy_price' => 'required|numeric|min:0', 'sell_price' => 'required|numeric|min:0']);
        $product = Product::create($data);
        return response()->json($product, 201);
    }

    public function productUpdate(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'buy_price' => 'required|numeric|min:0', 'sell_price' => 'required|numeric|min:0']);
        $product->update($data);
        return response()->json($product);
    }

    public function productDelete(Product $product): JsonResponse
    {
        if ($product->purchases()->exists() || $product->sales()->exists()) $this->fail('Impossible de supprimer ce produit : il est utilisé dans un achat ou une vente.');
        $product->delete();
        return response()->json(['ok' => true]);
    }

    public function stockUpdate(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate(['stock_quantity' => 'required|integer|min:0']);
        $product->update(['stock_quantity' => $data['stock_quantity']]);
        return response()->json($product);
    }

    public function clientStore(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'address' => 'nullable|string|max:255']);
        return response()->json(Client::create($data), 201);
    }

    public function clientUpdate(Request $request, Client $client): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'address' => 'nullable|string|max:255']);
        $client->update($data);
        return response()->json($client);
    }

    public function clientDelete(Client $client): JsonResponse
    {
        if ($client->sales()->exists()) $this->fail('Impossible de supprimer ce client : il est utilisé dans une vente.');
        $client->delete();
        return response()->json(['ok' => true]);
    }

    public function supplierStore(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'address' => 'nullable|string|max:255']);
        return response()->json(Supplier::create($data), 201);
    }

    public function supplierUpdate(Request $request, Supplier $supplier): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'address' => 'nullable|string|max:255']);
        $supplier->update($data);
        return response()->json($supplier);
    }

    public function supplierDelete(Supplier $supplier): JsonResponse
    {
        if ($supplier->purchases()->exists()) $this->fail('Impossible de supprimer ce fournisseur : il est utilisé dans un achat.');
        $supplier->delete();
        return response()->json(['ok' => true]);
    }

    public function purchaseStore(Request $request): JsonResponse
    {
        $data = $request->validate(['supplier_id' => 'required|exists:suppliers,id', 'product_id' => 'required|exists:products,id', 'quantity' => 'required|integer|min:1']);
        return response()->json(DB::transaction(function () use ($data) {
            $product = Product::lockForUpdate()->findOrFail($data['product_id']);
            $total = round($data['quantity'] * (float) $product->buy_price, 2);
            $cash = $this->totals()['cash'];
            if ($total > $cash + 0.0001) $this->fail('Achat impossible : solde disponible insuffisant. Disponible : '.number_format($cash, 2, ',', ' ').' DH. Achat : '.number_format($total, 2, ',', ' ').' DH.');
            $purchase = Purchase::create(['date' => now()->toDateString(), 'supplier_id' => $data['supplier_id'], 'product_id' => $product->id, 'quantity' => $data['quantity'], 'unit_price' => $product->buy_price, 'total' => $total]);
            $product->increment('stock_quantity', $data['quantity']);
            return $purchase->load(['product', 'supplier']);
        }), 201);
    }

    public function purchaseUpdate(Request $request, Purchase $purchase): JsonResponse
    {
        $data = $request->validate(['supplier_id' => 'required|exists:suppliers,id', 'product_id' => 'required|exists:products,id', 'quantity' => 'required|integer|min:1']);
        return response()->json(DB::transaction(function () use ($data, $purchase) {
            $purchase = Purchase::lockForUpdate()->findOrFail($purchase->id);
            $oldProduct = Product::lockForUpdate()->findOrFail($purchase->product_id);
            $oldProduct->decrement('stock_quantity', $purchase->quantity);
            $cash = $this->totals(['purchase' => $purchase->id])['cash'];
            $newProduct = Product::lockForUpdate()->findOrFail($data['product_id']);
            $total = round($data['quantity'] * (float) $newProduct->buy_price, 2);
            if ($total > $cash + 0.0001) {
                $oldProduct->increment('stock_quantity', $purchase->quantity);
                $this->fail('Modification impossible : solde disponible insuffisant. Disponible : '.number_format($cash, 2, ',', ' ').' DH. Nouvel achat : '.number_format($total, 2, ',', ' ').' DH.');
            }
            $newProduct->increment('stock_quantity', $data['quantity']);
            $purchase->update(['supplier_id' => $data['supplier_id'], 'product_id' => $newProduct->id, 'quantity' => $data['quantity'], 'unit_price' => $newProduct->buy_price, 'total' => $total]);
            return $purchase->fresh(['product', 'supplier']);
        }));
    }

    public function purchaseDelete(Purchase $purchase): JsonResponse
    {
        DB::transaction(function () use ($purchase) {
            $product = Product::lockForUpdate()->findOrFail($purchase->product_id);
            if ($product->stock_quantity < $purchase->quantity) $this->fail('Impossible de supprimer cet achat : le stock actuel ne permet pas de retirer cette quantité.');
            $product->decrement('stock_quantity', $purchase->quantity);
            $purchase->delete();
        });
        return response()->json(['ok' => true]);
    }

    public function saleStore(Request $request): JsonResponse
    {
        $data = $request->validate(['client_id' => 'required|exists:clients,id', 'product_id' => 'required|exists:products,id', 'quantity' => 'required|integer|min:1']);
        return response()->json(DB::transaction(function () use ($data) {
            $product = Product::lockForUpdate()->findOrFail($data['product_id']);
            if ($product->stock_quantity < $data['quantity']) $this->fail('Vente impossible : stock insuffisant. Stock disponible : '.$product->stock_quantity.'.');
            $total = round($data['quantity'] * (float) $product->sell_price, 2);
            $sale = Sale::create(['date' => now()->toDateString(), 'client_id' => $data['client_id'], 'product_id' => $product->id, 'quantity' => $data['quantity'], 'unit_price' => $product->sell_price, 'total' => $total]);
            $product->decrement('stock_quantity', $data['quantity']);
            return $sale->load(['product', 'client']);
        }), 201);
    }

    public function saleUpdate(Request $request, Sale $sale): JsonResponse
    {
        $data = $request->validate(['client_id' => 'required|exists:clients,id', 'product_id' => 'required|exists:products,id', 'quantity' => 'required|integer|min:1']);
        return response()->json(DB::transaction(function () use ($data, $sale) {
            $sale = Sale::lockForUpdate()->findOrFail($sale->id);
            $oldProduct = Product::lockForUpdate()->findOrFail($sale->product_id);
            $oldProduct->increment('stock_quantity', $sale->quantity);
            $newProduct = Product::lockForUpdate()->findOrFail($data['product_id']);
            if ($newProduct->stock_quantity < $data['quantity']) {
                $oldProduct->decrement('stock_quantity', $sale->quantity);
                $this->fail('Modification impossible : stock insuffisant. Stock disponible : '.$newProduct->stock_quantity.'.');
            }
            $total = round($data['quantity'] * (float) $newProduct->sell_price, 2);
            $newProduct->decrement('stock_quantity', $data['quantity']);
            $sale->update(['client_id' => $data['client_id'], 'product_id' => $newProduct->id, 'quantity' => $data['quantity'], 'unit_price' => $newProduct->sell_price, 'total' => $total]);
            return $sale->fresh(['product', 'client']);
        }));
    }

    public function saleDelete(Sale $sale): JsonResponse
    {
        DB::transaction(function () use ($sale) {
            Product::lockForUpdate()->findOrFail($sale->product_id)->increment('stock_quantity', $sale->quantity);
            $sale->delete();
        });
        return response()->json(['ok' => true]);
    }

    public function chargeStore(Request $request): JsonResponse
    {
        $data = $request->validate(['description' => 'required|string|max:255', 'amount' => 'required|numeric|min:0.01']);
        return response()->json(DB::transaction(function () use ($data) {
            $cash = $this->totals()['cash'];
            if ($data['amount'] > $cash + 0.0001) $this->fail('Charge impossible : solde disponible insuffisant.');
            return Charge::create(['date' => now()->toDateString(), ...$data]);
        }), 201);
    }

    public function chargeUpdate(Request $request, Charge $charge): JsonResponse
    {
        $data = $request->validate(['description' => 'required|string|max:255', 'amount' => 'required|numeric|min:0.01']);
        return response()->json(DB::transaction(function () use ($data, $charge) {
            $cash = $this->totals(['charge' => $charge->id])['cash'];
            if ($data['amount'] > $cash + 0.0001) $this->fail('Modification impossible : solde disponible insuffisant.');
            $charge->update($data);
            return $charge;
        }));
    }

    public function chargeDelete(Charge $charge): JsonResponse { $charge->delete(); return response()->json(['ok' => true]); }

    public function salaryStore(Request $request): JsonResponse
    {
        $data = $request->validate(['description' => 'required|string|max:255', 'amount' => 'required|numeric|min:0.01']);
        return response()->json(DB::transaction(function () use ($data) {
            $profit = $this->totals()['profit'];
            if ($data['amount'] > $profit + 0.0001) $this->fail('Salaire impossible : bénéfice disponible insuffisant. Disponible : '.number_format($profit, 2, ',', ' ').' DH.');
            return Salary::create(['date' => now()->toDateString(), ...$data]);
        }), 201);
    }

    public function salaryUpdate(Request $request, Salary $salary): JsonResponse
    {
        $data = $request->validate(['description' => 'required|string|max:255', 'amount' => 'required|numeric|min:0.01']);
        return response()->json(DB::transaction(function () use ($data, $salary) {
            $profit = $this->totals(['salary' => $salary->id])['profit'];
            if ($data['amount'] > $profit + 0.0001) $this->fail('Modification impossible : bénéfice disponible insuffisant.');
            $salary->update($data);
            return $salary;
        }));
    }

    public function salaryDelete(Salary $salary): JsonResponse { $salary->delete(); return response()->json(['ok' => true]); }

    public function capitalStore(Request $request): JsonResponse
    {
        $data = $request->validate(['type' => 'required|in:Capital initial,Augmentation - apport personnel,Augmentation - bénéfice', 'amount' => 'required|numeric|min:0.01']);
        return response()->json(DB::transaction(function () use ($data) {
            if ($data['type'] === 'Augmentation - bénéfice') {
                $profit = $this->totals()['profit'];
                if ($data['amount'] > $profit + 0.0001) $this->fail('Augmentation impossible : bénéfice disponible insuffisant. Disponible : '.number_format($profit, 2, ',', ' ').' DH.');
                $movement = false;
            } else {
                $movement = true;
            }
            return CapitalOperation::create(['date' => now()->toDateString(), 'type' => $data['type'], 'amount' => $data['amount'], 'movement' => $movement]);
        }), 201);
    }

    public function capitalUpdate(Request $request, CapitalOperation $capitalOperation): JsonResponse
    {
        $data = $request->validate(['type' => 'required|in:Capital initial,Augmentation - apport personnel,Augmentation - bénéfice', 'amount' => 'required|numeric|min:0.01']);
        return response()->json(DB::transaction(function () use ($data, $capitalOperation) {
            $base = $this->totals(['capital' => $capitalOperation->id]);
            if ($data['type'] === 'Augmentation - bénéfice') {
                if ($data['amount'] > $base['profit'] + 0.0001) $this->fail('Modification impossible : bénéfice disponible insuffisant.');
                $movement = false;
            } else {
                $movement = true;
            }
            $capitalOperation->update(['type' => $data['type'], 'amount' => $data['amount'], 'movement' => $movement]);
            return $capitalOperation;
        }));
    }

    public function capitalDelete(CapitalOperation $capitalOperation): JsonResponse
    {
        if ($capitalOperation->type === 'Augmentation - bénéfice') {
            $capitalOperation->delete();
            return response()->json(['ok' => true]);
        }
        $capitalOperation->delete();
        return response()->json(['ok' => true]);
    }
}
