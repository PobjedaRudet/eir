<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->timestamp('forwarded_at')->nullable()->after('sent_at');
            $table->foreignId('handled_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->string('supplier_name', 200)->nullable()->after('handled_by');
            $table->string('supplier_email', 200)->nullable()->after('supplier_name');
            $table->text('procurement_note')->nullable()->after('note');
        });

        DB::table('service_orders')
            ->where('status', 'sent')
            ->update([
                'status' => 'sent_to_supplier',
                'forwarded_at' => DB::raw('sent_at'),
            ]);
    }

    public function down(): void
    {
        DB::table('service_orders')
            ->where('status', 'sent_to_supplier')
            ->update(['status' => 'sent']);

        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropForeign(['handled_by']);
            $table->dropColumn(['forwarded_at', 'handled_by', 'supplier_name', 'supplier_email', 'procurement_note']);
        });
    }
};
