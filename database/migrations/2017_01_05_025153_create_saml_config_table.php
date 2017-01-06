<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSamlConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'saml_config',
            function (Blueprint $t){
                $t->integer('service_id')->unsigned()->primary();
                $t->foreign('service_id')->references('id')->on('service')->onDelete('cascade');
                $t->integer('default_role')->unsigned()->nullable();
                $t->foreign('default_role')->references('id')->on('role');
                $t->text('sp_nameIDFormat')->nullable();
                $t->text('sp_x509cert')->nullable();
                $t->text('sp_privateKey')->nullable();
                $t->text('relay_state')->nullable();
                $t->text('idp_entityId');
                $t->text('idp_singleSignOnService_url');
                //$t->text('idp_singleLogoutService_url');
                $t->text('idp_x509cert')->nullable();
                //$t->boolean('strict')->default(0);
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('saml_config');
    }
}
