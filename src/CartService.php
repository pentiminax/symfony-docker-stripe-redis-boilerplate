<?php

namespace App;

use App\Entity\Cart;
use App\Entity\CartProduct;
use App\Service\SessionService;
use Talleu\RedisOm\Om\RedisObjectManagerInterface;

class CartService
{
    public function __construct(
        private readonly RedisObjectManagerInterface $redisObjectManager,
        private readonly SessionService $sessionService
    ) {
    }

    public function addProductToCart(string $productId): Cart
    {
        $cart = $this->getCart();

        $cartProduct = new CartProduct($productId, 1);

        if (isset($cart->products[$productId])) {
            $cart->products[$productId]->quantity++;
        } else {
            $cart->products[$productId] = $cartProduct;
        }

        $this->redisObjectManager->persist($cart);
        $this->redisObjectManager->flush();

        return $cart;
    }

    public function getCart(): Cart
    {
        $cartId = $this->sessionService->getCartId();
        $cart = $this->redisObjectManager->find(Cart::class, $cartId);


        if (!$cart) {
            $cart = new Cart();

            $cart->id = $cartId;
            $this->redisObjectManager->persist($cart);
            $this->redisObjectManager->flush();
        }

        return $cart;
    }
}
