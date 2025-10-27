<?php
/*
|--------------------------------------------------------------------------
| FoodieLand — Laravel Migration Files
| Single file containing example migration classes for FoodieLand platform.
| Paste each class into its own migration file when integrating into a Laravel project.
| Filename convention suggested: YYYY_MM_DD_HHMMSS_create_<table>_table.php
|--------------------------------------------------------------------------
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* --------------------------------------------------------------------------
| 1) create_users_table
| Includes authentication fields, profile fields and social login support
| -------------------------------------------------------------------------- */
class CreateUsersTable extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('username')->nullable()->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('profile_picture')->nullable();
            $table->text('bio')->nullable();
            $table->json('social_handles')->nullable(); // e.g. {"instagram":"...","facebook":"..."}
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
}


/* --------------------------------------------------------------------------
| 2) create_password_resets_table
| Standard Laravel-style password reset tokens
| -------------------------------------------------------------------------- */
class CreatePasswordResetsTable extends Migration
{
    public function up()
    {
        Schema::create('password_resets', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('password_resets');
    }
}


/* --------------------------------------------------------------------------
| 3) create_social_accounts_table
| Stores OAuth/social login external accounts (Google, Facebook, etc.)
| -------------------------------------------------------------------------- */
class CreateSocialAccountsTable extends Migration
{
    public function up()
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('provider'); // e.g. google, facebook
            $table->string('provider_user_id');
            $table->string('provider_email')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['provider', 'provider_user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('social_accounts');
    }
}


/* --------------------------------------------------------------------------
| 4) create_categories_table
| For both recipes and blog post categorization (we'll separate relation tables)
| -------------------------------------------------------------------------- */
class CreateCategoriesTable extends Migration
{
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['recipe', 'blog', 'general'])->default('general');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('categories');
    }
}


/* --------------------------------------------------------------------------
| 5) create_recipes_table
| Core recipe information; author stored via user_id
| -------------------------------------------------------------------------- */
class CreateRecipesTable extends Migration
{
    public function up()
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index(); // author
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('short_description')->nullable();
            $table->text('featured_image')->nullable();
            $table->integer('prep_time_minutes')->nullable();
            $table->integer('cook_time_minutes')->nullable();
            $table->integer('servings')->nullable();
            $table->decimal('rating', 3, 2)->default(0); // aggregate rating
            $table->unsignedInteger('ratings_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('recipes');
    }
}


/* --------------------------------------------------------------------------
| 6) create_recipe_images_table
| Stores additional images for recipes (optional ordering)
| -------------------------------------------------------------------------- */
class CreateRecipeImagesTable extends Migration
{
    public function up()
    {
        Schema::create('recipe_images', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('recipe_id')->index();
            $table->string('path');
            $table->string('alt')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->foreign('recipe_id')->references('id')->on('recipes')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('recipe_images');
    }
}


/* --------------------------------------------------------------------------
| 7) create_ingredients_table
| Ingredients master list to reuse ingredients, with pivot to recipes
| -------------------------------------------------------------------------- */
class CreateIngredientsTable extends Migration
{
    public function up()
    {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('unit')->nullable(); // e.g. g, tbsp
            $table->timestamps();

            $table->unique(['name']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('ingredients');
    }
}


/* --------------------------------------------------------------------------
| 8) create_recipe_ingredient_table (pivot)
| Stores ingredient quantities and position/order for a recipe
| -------------------------------------------------------------------------- */
class CreateRecipeIngredientTable extends Migration
{
    public function up()
    {
        Schema::create('recipe_ingredient', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('recipe_id')->index();
            $table->unsignedBigInteger('ingredient_id')->index();
            $table->string('quantity')->nullable(); // freeform: "2 cups", "1 tsp"
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->foreign('recipe_id')->references('id')->on('recipes')->onDelete('cascade');
            $table->foreign('ingredient_id')->references('id')->on('ingredients')->onDelete('cascade');
            $table->unique(['recipe_id','ingredient_id','order']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('recipe_ingredient');
    }
}


/* --------------------------------------------------------------------------
| 9) create_preparation_steps_table
| Step-by-step guide for recipes (supports text + optional image)
| -------------------------------------------------------------------------- */
class CreatePreparationStepsTable extends Migration
{
    public function up()
    {
        Schema::create('preparation_steps', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('recipe_id')->index();
            $table->unsignedInteger('step_number');
            $table->text('instruction');
            $table->string('image')->nullable();
            $table->timestamps();

            $table->foreign('recipe_id')->references('id')->on('recipes')->onDelete('cascade');
            $table->unique(['recipe_id','step_number']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('preparation_steps');
    }
}


/* --------------------------------------------------------------------------
| 10) recipe_category pivot table
| Connect recipes to categories (many-to-many)
| -------------------------------------------------------------------------- */
class CreateCategoryRecipeTable extends Migration
{
    public function up()
    {
        Schema::create('category_recipe', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->index();
            $table->unsignedBigInteger('recipe_id')->index();
            $table->primary(['category_id','recipe_id']);

            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('recipe_id')->references('id')->on('recipes')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('category_recipe');
    }
}


/* --------------------------------------------------------------------------
| 11) create_blogs_table
| Blog posts authored by users
| -------------------------------------------------------------------------- */
class CreateBlogsTable extends Migration
{
    public function up()
    {
        Schema::create('blogs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('short_description')->nullable();
            $table->text('content');
            $table->string('featured_image')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('blogs');
    }
}


/* --------------------------------------------------------------------------
| 12) blog_category pivot table
| Connect blogs to categories
| -------------------------------------------------------------------------- */
class CreateBlogCategoryTable extends Migration
{
    public function up()
    {
        Schema::create('blog_category', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->index();
            $table->unsignedBigInteger('blog_id')->index();
            $table->primary(['category_id','blog_id']);

            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('blog_id')->references('id')->on('blogs')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('blog_category');
    }
}


/* --------------------------------------------------------------------------
| 13) create_contact_submissions_table
| Stores messages sent from the Contact Us page (admin mailbox)
| Also optionally links to a user (if logged-in user sent it)
| -------------------------------------------------------------------------- */
class CreateContactSubmissionsTable extends Migration
{
    public function up()
    {
        Schema::create('contact_submissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable()->index(); // if sender was logged in
            $table->string('name');
            $table->string('email');
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('contact_submissions');
    }
}


/* --------------------------------------------------------------------------
| 14) optional: create_recipe_ratings_table
| Tracks individual ratings so you can compute aggregates safely
| -------------------------------------------------------------------------- */
class CreateRecipeRatingsTable extends Migration
{
    public function up()
    {
        Schema::create('recipe_ratings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('recipe_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->tinyInteger('rating')->unsigned(); // 1-5
            $table->text('review')->nullable();
            $table->timestamps();

            $table->foreign('recipe_id')->references('id')->on('recipes')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->unique(['recipe_id','user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('recipe_ratings');
    }
}


/* --------------------------------------------------------------------------
| 15) optional: create_follows_table
| Allow users to follow other chefs (useful for profile & feed)
| -------------------------------------------------------------------------- */
class CreateFollowsTable extends Migration
{
    public function up()
    {
        Schema::create('follows', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('follower_id')->index();
            $table->unsignedBigInteger('followed_id')->index();
            $table->timestamps();

            $table->foreign('follower_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('followed_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['follower_id','followed_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('follows');
    }
}


/* --------------------------------------------------------------------------
| End of file
| Notes:
| - Split each class into its own migration file when adding to a Laravel app.
| - Add indexes and foreign keys as desired for performance.
| - Adjust column types/lengths to your project preferences.
*/
