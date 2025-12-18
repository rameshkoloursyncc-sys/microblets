

---- then add this universal search bar right to titel microble , ---





----

<form class="max-w-md mx-auto">   

    <label for="search" class="block mb-2.5 text-sm font-medium text-heading sr-only ">Search</label>

    <div class="relative">

        <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">

            <svg class="w-4 h-4 text-body" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m21 21-3.5-3.5M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/></svg>

        </div>

        <input type="search" id="search" class="block w-full p-3 ps-9 bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-base focus:ring-brand focus:border-brand shadow-xs placeholder:text-body" placeholder="Search" required />

        <button type="button" class="absolute end-1.5 bottom-1.5 text-white bg-brand hover:bg-brand-strong box-border border border-transparent focus:ring-4 focus:ring-brand-medium shadow-xs font-medium leading-5 rounded text-xs px-3 py-1.5 focus:outline-none">Search</button>

    </div>

</form>

-----



---- the same twice for -- section and other for size both seperate and indiviual 



--------for raw material -----------

`carbon, chemical,soft/stiff cord,fabric,oil,others,resign,tpu,fibre glass cord , steel wire , packing material ` ----,,



User Seeding Commands
Create individual users:

# Create a regular user
php artisan tinker --execute="App\Models\User::create(['name' => 'user1', 'password' => bcrypt('password123'), 'role' => 'user']);"

# Create an admin user
php artisan tinker --execute="App\Models\User::create(['name' => 'admin1', 'password' => bcrypt('admin123'), 'role' => 'admin']);"
Product Seeding Commands
For Vee Belts:

php artisan db:seed --class=VeeBeltSeeder
For Cogged Belts:

php artisan db:seed --class=CoggedBeltSeeder
For Poly Belts:

php artisan db:seed --class=PolyBeltSeeder
For TPU Belts:

php artisan db:seed --class=TpuBeltSeeder
For Rate Formulas:

php artisan db:seed --class=RateFormulaSeeder
Seed all at once:

php artisan db:seed
Or if you have specific production seeding scripts:

# Run your existing production seeding scripts
./seed_vee_belts_production.sh
./seed_cogged_belts_production.sh
./seed_poly_belts_production.sh
./seed_tpu_belts_production.sh
./seed_all_tpu_belts_production.sh
The authentication system is now working properly with:

✅ Default admin: koloursyncc / kolorsync1010 (hardcoded)
✅ Database users can be created via User Management
✅ Email field is now nullable
✅ Session-based authentication working
✅ Role-based access control