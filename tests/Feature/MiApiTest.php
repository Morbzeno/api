<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Category;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductsCart;
use App\Models\Brand;
class MiApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function puede_listar_todas_las_categorias()
    {
        // Crear algunas categorías en la base de datos
        Category::factory()->count(3)->create();

        // Llamar al endpoint
        $response = $this->getJson('/api/category');

        // Verificar la respuesta
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'data' => [
                         '*' => ['id', 'name', 'tags', 'description', 'created_at', 'updated_at']
                     ]
                 ]);
    }

    /** @test */
    public function puede_crear_una_categoria()
    {
        $categoriaData = [
            'name' => 'Electrónica',
            'tags' => ['tecnología', 'gadgets'],
            'description' => 'Categoría de productos electrónicos'
        ];

        $response = $this->postJson('/api/category', $categoriaData);

        $response->assertStatus(201)
                 ->assertJson([
                     'message' => 'categoria insertada correctamente',
                     'data' => [
                         'name' => 'Electrónica',
                         'tags' => ['tecnología', 'gadgets'],
                         'description' => 'Categoría de productos electrónicos'
                     ]
                 ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Electrónica',
            'description' => 'Categoría de productos electrónicos'
        ]);
    }

    /** @test */
    public function puede_mostrar_una_categoria_especifica($id)
    {
        $categoria = Category::find($id);

        $response = $this->getJson("/api/category/{$categoria->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'id' => $categoria->id,
                     'name' => $categoria->name,
                     'tags' => $categoria->tags,
                     'description' => $categoria->description
                 ]);
    }
    /** @test */
    public function puede_agregar_un_producto_al_carrito()
    {
        // Crear un producto en la base de datos
        $product = Product::factory()->create([
            'retail_price' => 100.50
        ]);

        // Simular una solicitud para añadir el producto al carrito
        $response = $this->postJson('/api/cart/add', [
            'client_id' => '123456',  // Simulación de ID de cliente
            'id' => $product->id,
            'quantity' => 2
        ]);

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'cart' => [
                         'client_id' => '123456',
                         'status' => 'pending'
                     ]
                 ]);

        // Verificar que el producto se haya añadido correctamente al carrito
        $this->assertDatabaseHas('products_cart', [
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 100.50,
            'subtotal' => 201.00, // 100.50 * 2
            'state' => 'waiting'
        ]);
    }
       /** @test */
       public function puede_listar_productos()
       {
           $category = Category::factory()->create();
           $brand = Brand::factory()->create();
           Product::factory()->count(3)->create([
               'category_id' => $category->id,
               'brand_id' => $brand->id,
           ]);
   
           $response = $this->getJson('/api/products');
           $response->assertStatus(200)->assertJsonStructure(['message', 'data']);
       }
   
       /** @test */
       public function puede_agregar_un_producto()
       {
           $category = Category::factory()->create();
           $brand = Brand::factory()->create();
   
           $response = $this->postJson('/api/products', [
               'category_id' => $category->id,
               'name' => 'Producto de prueba',
               'brand_id' => $brand->id,
               'retail_price' => 100.50,
               'buy_price' => 80.00,
               'bar_code' => '123456789',
               'stock' => 10,
               'description' => 'Descripción de prueba',
               'state' => 'available',
               'wholesale_price' => 90.00,
               'sku' => 'TEST123',
           ]);
   
           $response->assertStatus(201)->assertJson(['message' => 'Producto insertado correctamente']);
           $this->assertDatabaseHas('products', ['name' => 'Producto de prueba']);
       }
   
       /** @test */
       public function puede_mostrar_un_producto()
       {
           $product = Product::factory()->create();
           $response = $this->getJson("/api/products/{$product->id}");
           $response->assertStatus(200)->assertJsonFragment(['id' => $product->id]);
       }
   
       /** @test */
       public function puede_actualizar_un_producto()
       {
           $product = Product::factory()->create();
           $response = $this->putJson("/api/products/{$product->id}", [
               'name' => 'Nuevo Nombre',
           ]);
   
           $response->assertStatus(201)->assertJson(['message' => 'Producto insertado correctamente']);
           $this->assertDatabaseHas('products', ['name' => 'Nuevo Nombre']);
       }
   
       /** @test */
       public function puede_eliminar_un_producto()
       {
           $product = Product::factory()->create();
           $response = $this->deleteJson("/api/products/{$product->id}");
   
           $response->assertStatus(200)->assertJson(['message' => 'producto eliminado']);
           $this->assertDatabaseMissing('products', ['id' => $product->id]);
       }
   
       /** @test */
       public function puede_aumentar_el_stock_de_un_producto()
       {
           $product = Product::factory()->create(['stock' => 5]);
           $response = $this->putJson("/api/products/{$product->id}/moreStock", ['aumento' => 10]);
           
           $response->assertStatus(200)->assertJson(['message' => "se añadieron: 10 de: {$product->name}"]);
           $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 15]);
       }

}
