<?php

namespace App\Service;

use App\Entity\Cart;
use Stripe\Exception\ApiErrorException;
use Stripe\Price;
use Stripe\Product;
use Stripe\StripeClient;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class StripeService
{
    private StripeClient $client;

    public function __construct(
        #[Autowire('%env(STRIPE_API_KEY)%')] string $apiKey
    ) {
        $this->client = new StripeClient($apiKey);
    }

    /**
     * @return Product[]
     * @throws ApiErrorException
     */
    public function getActiveProducts(): array
    {
        return $this
            ->client
            ->products
            ->all(['active' => true])
            ->data;
    }

    public function findOneProduct(string $productId): Product
    {
        return $this->client->products->retrieve($productId);
    }

    public function getLastActivePrice(Product $product): ?Price
    {
        return  $this->client->prices->all([
            'product' => $product->id,
            'active' => true,
            'limit' => 1
        ])->data[0] ?? null;
    }

    public function getCartBuyUrl(Cart $cart): string
    {
        $lineItems = [];

        foreach ($cart->products as $cartProduct) {
            $product = $this->findOneProduct($cartProduct->id);
            $price = $this->getLastActivePrice($product);
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $product->name,
                        'images' => $product->images
                    ],
                    'unit_amount' => $price->unit_amount
                ],
                'quantity' => $cartProduct->quantity,
            ];
        }

        return $this->client->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => 'http://localhost',
            'cancel_url' => 'http://localhost',
        ])->url;
    }

    public function getProductBuyUrl(Product $product, int $quantity = 1): string
    {
        $price = $this->getLastActivePrice($product);

        return $this->client->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $product->name,
                        'images' => $product->images
                    ],
                    'unit_amount' => $price->unit_amount
                ],
                'quantity' => $quantity,
            ]],
            'mode' => 'payment',
            'success_url' => 'https://localhost',
            'cancel_url' => 'https://localhost',
        ])->url;
    }
}
