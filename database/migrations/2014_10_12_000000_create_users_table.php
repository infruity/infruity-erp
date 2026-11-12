<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->integer('id_user', true);
            $table->string('nickname', 30)->nullable();
            $table->string('nm_user', 100)->nullable();
            $table->string('username', 100)->nullable();
            $table->string('telp', 16)->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_aktif')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        \DB::table('users')->delete();

        \DB::table('users')->insert(array(
            array(
                'nm_user' => 'MOCH IRSYADUL ANAM',
                'nickname' => 'irsyad',
                'username' => 'irsyaad',
                'email' => 'irsyad7798@gmail.com',
                'password' => '$2y$10$wzqj/2.YIrD7bBbCb.ndhuoDktih2.bulhQQmpi6j5mXxQHdeFf.q',
                'telp' => '08674514312331',
            ),
            array(
                'nm_user' => 'Data Management',
                'nickname' => 'datamangement',
                'username' => 'datamangement@infruity.com',
                'email' => 'datamangement@infruity.com',
                'password' => Hash::make('admin123'),
                'telp' => '08674514312331',
            ),         
            array(
                'nm_user' => 'Store Admin',
                'nickname' => 'storeadmin',
                'username' => 'storeadmin@infruity.com',
                'email' => 'storeadmin@infruity.com',
                'password' => Hash::make('admin123'),
                'telp' => '08674514312331',
            ),
            array(
                'nm_user' => 'Store Crew 1',
                'nickname' => 'storecrew1',
                'username' => 'storecrew1@infruity.com',
                'email' => 'storecrew1@infruity.com',
                'password' => Hash::make('admin123'),
                'telp' => '08674514312331',
            ),
            array(
                'nm_user' => 'Store Crew 2',
                'nickname' => 'storecrew2',
                'username' => 'storecrew2@infruity.com',
                'email' => 'storecrew2@infruity.com',
                'password' => Hash::make('admin123'),
                'telp' => '08674514312331',
            ),
            array(
                'nm_user' => 'Morning Booth Crew - 1',
                'nickname' => 'morningbooth1',
                'username' => 'morningbooth1@infruity.com',
                'email' => 'morningbooth1@infruity.com',
                'password' => Hash::make('admin123'),
                'telp' => '08674514312331',
            ),
            array(
                'nm_user' => 'Morning Booth Crew - 2',
                'nickname' => 'morningbooth2',
                'username' => 'morningbooth2@infruity.com',
                'email' => 'morningbooth2@infruity.com',
                'password' => Hash::make('admin123'),
                'telp' => '08674514312331',
            ),
            array(
                'nm_user' => 'Morning Booth Crew - 3',
                'nickname' => 'morningbooth3',
                'username' => 'morningbooth3@infruity.com',
                'email' => 'morningbooth3@infruity.com',
                'password' => Hash::make('admin123'),
                'telp' => '08674514312331',
            ),
            array(
                'nm_user' => 'Morning Booth Crew - 4',
                'nickname' => 'morningbooth4',
                'username' => 'morningbooth4@infruity.com',
                'email' => 'morningbooth4@infruity.com',
                'password' => Hash::make('admin123'),
                'telp' => '08674514312331',
            ),
            array(
                'nm_user' => 'Distribution Crew 1',
                'nickname' => 'distributioncrew1',
                'username' => 'distributioncrew1@infruity.com',
                'email' => 'distributioncrew1@infruity.com',
                'password' => Hash::make('admin123'),
                'telp' => '08674514312331',
            ),
            array(
                'nm_user' => 'Distribution Crew 2',
                'nickname' => 'distributioncrew2',
                'username' => 'distributioncrew2@infruity.com',
                'email' => 'distributioncrew2@infruity.com',
                'password' => Hash::make('admin123'),
                'telp' => '08674514312331',
            ),
            array(
                'nm_user' => 'Content Creator',
                'nickname' => 'contentcreator',
                'username' => 'contentcreator@infruity.com',
                'email' => 'contentcreator@infruity.com',
                'password' => Hash::make('admin123'),
                'telp' => '08674514312331',
            ),
            array(
                'nm_user' => 'Outlet Crew',
                'nickname' => 'outletcrew',
                'username' => 'outletcrew@infruity.com',
                'email' => 'outletcrew@infruity.com',
                'password' => Hash::make('admin123'),
                'telp' => '08674514312331',
            ),
            array(
                'nm_user' => 'Courier Crew',
                'nickname' => 'couriercrew',
                'username' => 'couriercrew@infruity.com',
                'email' => 'couriercrew@infruity.com',
                'password' => Hash::make('admin123'),
                'telp' => '08674514312331',
            ),
            array(
                'nm_user' => 'Helper Crew',
                'nickname' => 'helpercrew',
                'username' => 'helpercrew@infruity.com',
                'email' => 'helpercrew@infruity.com',
                'password' => Hash::make('admin123'),
                'telp' => '08674514312331',
            ),            
            array(
                'nm_user' => 'Achmad Noorman Setiawan',
                'nickname' => 'noorman',
                'username' => 'noorman@infruity.com',
                'email' => 'noorman@infruity.com',
                'password' => Hash::make('noormangans'),
                'telp' => '08674514312331',
            ),
        ));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
