<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $expenses = [
            // 1. Operational Expense
            ['category' => 'Operational Expenses', 'item' => 'Rent or Lease', 'description' => 'Rental or lease payments for production facilities or poultry sheds. Essential for both businesses.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category' => 'Operational Expenses', 'item' => 'Security Services', 'description' => 'Payments for security staff or systems to safeguard premises, equipment, and livestock.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category' => 'Operational Expenses', 'item' => 'Cleaning & Sanitation Supplies', 'description' => 'Costs of cleaning materials for machinery, floors, or poultry houses to maintain hygiene standards.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],

            // 2. Raw Material & Input Costs
            ['category' => 'Raw Material & Input Costs', 'item' => 'Primary Raw Materials', 'description' => 'Main inputs for production (e.g., metals, chemicals for manufacturing; chicks/eggs for poultry).', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category' => 'Raw Material & Input Costs', 'item' => 'Packing Materials', 'description' => 'Bags, cartons, or other packing items for finished goods or eggs/chicken in poultry.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category' => 'Raw Material & Input Costs', 'item' => 'Auxiliary Materials', 'description' => 'Secondary inputs like lubricants, adhesives, or additives.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],

            // 3. Labor & Staffing
            ['category' => 'Labor & Staffing', 'item' => 'Salaries & Wages', 'description' => 'Payments to regular and casual employees, including bonuses and overtime.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category' => 'Labor & Staffing', 'item' => 'Contract/Temporary Labor', 'description' => 'Payments for seasonal or temporary workers (common in both).', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category' => 'Labor & Staffing', 'item' => 'Employee Welfare & Benefits', 'description' => 'Medical, insurance, canteen, uniforms, housing (sometimes provided in poultry farms).', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category' => 'Labor & Staffing', 'item' => 'Training & Development', 'description' => 'Skill enhancement for workers or upskilling farmhands in poultry.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],

            // 4. Utilities & Overheads
            ['category' => 'Utilities & Overheads', 'item' => 'Electricity', 'description' => 'Power for machines, lighting, incubators, or temperature control in poultry.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category' => 'Utilities & Overheads', 'item' => 'Water Supply', 'description' => 'Process use, cleaning, and for drinking (essential in poultry).', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category' => 'Utilities & Overheads', 'item' => 'Fuel & Gas', 'description' => 'Boilers, generators, or delivery vehicles.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category' => 'Utilities & Overheads', 'item' => 'Waste Disposal', 'description' => 'Removal of process or biological waste.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],

            // 5. Maintenance & Repairs
            ['category' => 'Maintenance & Repairs', 'item' => 'Machinery & Equipment Maintenance', 'description' => 'Upkeep of production lines, farm machinery, feeding systems, or generators.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category' => 'Maintenance & Repairs', 'item' => 'Building & Premises Maintenance', 'description' => 'Repairs to factory structure or poultry sheds, fencing, roofing, etc.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category' => 'Maintenance & Repairs', 'item' => 'Vehicle Maintenance', 'description' => 'Servicing delivery vans, tractors, or egg-transport vehicles.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],

            // 6. Administrative & Office Expenses
            ['category' => 'Administrative & Office Expenses', 'item' => 'Office Supplies', 'description' => 'Stationery, printing, computers, furniture.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category' => 'Administrative & Office Expenses', 'item' => 'Telephone & Internet', 'description' => 'Communication expenses.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category' => 'Administrative & Office Expenses', 'item' => 'Software & IT Services', 'description' => 'ERP, accounting software, or farm management systems.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category' => 'Administrative & Office Expenses', 'item' => 'Bank Charges', 'description' => 'Loan processing fees, account maintenance.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],

            // 7. Logistics & Transportation
            ['category' => 'Logistics & Transportation', 'item' => 'Inbound Freight', 'description' => 'Raw materials or feed delivery costs.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category' => 'Logistics & Transportation', 'item' => 'Outbound Freight', 'description' => 'Distribution of finished goods or live/processed birds/eggs.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category' => 'Logistics & Transportation', 'item' => 'Courier & Postage', 'description' => 'Documents, small parcels, or sample shipping.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],

            // 8. Veterinary/Health (Poultry Unique/Prominent)
            ['category' => 'Veterinary & Health', 'item' => 'Veterinary Medicines & Vaccines', 'description' => 'Health and disease prevention for poultry.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category' => 'Veterinary & Health', 'item' => 'Veterinary Consultation', 'description' => 'Fees for regular check-ups, health audits, or emergency visits.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category' => 'Veterinary & Health', 'item' => 'Biosecurity Measures', 'description' => 'Disinfectants, pest control, and protective clothing.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],

            // 9. Feed & Nutrition (Poultry-Specific)
            ['category' => 'Feed & Nutrition', 'item' => 'Feed Purchases', 'description' => 'Grains, commercial feed mixes, supplements.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category' => 'Feed & Nutrition', 'item' => 'Feed Storage & Handling', 'description' => 'Bins, silos, feed delivery systems.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category' => 'Feed & Nutrition', 'item' => 'Feed Analysis & Quality Testing', 'description' => 'Lab testing to ensure nutritional content.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],

            // 10. Compliance, Licenses & Taxes
            ['category' => 'Compliance, Licenses & Taxes', 'item' => 'Government Licenses & Permits', 'description' => 'Factory licenses, FSSAI, FDA, animal welfare, pollution control, etc.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category' => 'Compliance, Licenses & Taxes', 'item' => 'Environmental Compliance', 'description' => 'Wastewater, emission, animal welfare standards (often more stringent in poultry).', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category' => 'Compliance, Licenses & Taxes', 'item' => 'Taxes & Duties', 'description' => 'GST, excise, property tax, custom duties on imported materials, etc.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category' => 'Compliance, Licenses & Taxes', 'item' => 'Audit & Legal Fees', 'description' => 'Annual audits, compliance checks, legal consultations.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],

            // 11. Depreciation & Asset Management
            ['category' => 'Depreciation & Asset Management', 'item' => 'Depreciation – Plant & Machinery', 'description' => 'Scheduled allocation of asset costs over useful life.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category' => 'Depreciation & Asset Management', 'item' => 'Depreciation – Vehicles', 'description' => 'Trucks, delivery vans, tractors.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category' => 'Depreciation & Asset Management', 'item' => 'Depreciation – Buildings', 'description' => 'Factories, sheds, office blocks.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],

            // 12. Other Expenses / Miscellaneous
            ['category' => 'Miscellaneous', 'item' => 'Insurance (Property, Stock, Livestock)', 'description' => 'Covers fire, theft, livestock disease, etc.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category' => 'Miscellaneous', 'item' => 'Interest on Loans', 'description' => 'Finance cost for working capital or equipment loans.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category' => 'Miscellaneous', 'item' => 'Research & Development', 'description' => 'Product/process innovation in manufacturing; breed/feed improvement in poultry.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category' => 'Miscellaneous', 'item' => 'Marketing, Advertising & Promotion', 'description' => 'Trade shows, banners, digital marketing.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ];

        DB::table('expense_heads')->insert($expenses);
    }
}
