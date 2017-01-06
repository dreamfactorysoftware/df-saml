<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateUserTableForSamlSupport extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('user', 'saml')) {
            Schema::table(
                'user',
                function (Blueprint $t){
                    $t->string('saml', 50)->nullable()->after('oauth_provider');
                }
            );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('user', 'saml')) {
            Schema::table(
                'user',
                function (Blueprint $t){
                    $t->dropColumn('saml');
                }
            );
        }
    }
}
