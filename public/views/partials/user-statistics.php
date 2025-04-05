<div class="flex flex-wrap -mx-3">
    <!-- Total Bookings Stats -->
    <div class="w-full md:w-1/3 px-3 mb-6">
        <div class="bg-white rounded-lg shadow-md p-6 stats-card">
            <div class="flex justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-semibold">Wszystkie rezerwacje</p>
                    <h3 class="text-2xl font-bold text-gray-800">
                        <?= $statistics['total_bookings'] ?? 0 ?>
                    </h3>
                </div>
                <div class="bg-blue-100 rounded-full h-12 w-12 flex items-center justify-center">
                    <i class="fas fa-calendar-check text-blue-500 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 flex justify-between text-xs font-medium">
                <span class="text-gray-500">
                    <span class="font-semibold text-green-600">
                        <?= $statistics['completed_bookings'] ?? 0 ?>
                    </span> ukończonych
                </span>
                <span class="text-gray-500">
                    <span class="font-semibold text-blue-600">
                        <?= $statistics['active_bookings'] ?? 0 ?>
                    </span> aktywnych
                </span>
            </div>
        </div>
    </div>

    <!-- Revenue Stats -->
    <div class="w-full md:w-1/3 px-3 mb-6">
        <div class="bg-white rounded-lg shadow-md p-6 stats-card">
            <div class="flex justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-semibold">Całkowite wydatki</p>
                    <h3 class="text-2xl font-bold text-gray-800">
                        <?= formatCurrency($statistics['total_payments'] ?? 0) ?>
                    </h3>
                </div>
                <div class="bg-green-100 rounded-full h-12 w-12 flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-green-500 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 text-xs font-medium">
                <div class="flex items-center justify-between">
                    <span class="text-gray-500">Średnio za rezerwację</span>
                    <span class="font-semibold">
                        <?php
                            $avgPerBooking = 0;
                            if (($statistics['total_bookings'] ?? 0) > 0) {
                                $avgPerBooking = ($statistics['total_payments'] ?? 0) / $statistics['total_bookings'];
                            }
                            echo formatCurrency($avgPerBooking);
                        ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Stats -->
    <div class="w-full md:w-1/3 px-3 mb-6">
        <div class="bg-white rounded-lg shadow-md p-6 stats-card">
            <div class="flex justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-semibold">Dni korzystania</p>
                    <h3 class="text-2xl font-bold text-gray-800">
                        <?= $statistics['total_rental_days'] ?? 0 ?>
                    </h3>
                </div>
                <div class="bg-purple-100 rounded-full h-12 w-12 flex items-center justify-center">
                    <i class="fas fa-clock text-purple-500 text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <div class="flex justify-between items-center text-xs">
                    <span class="text-gray-500">Najdłuższa rezerwacja</span>
                    <span class="font-semibold">
                        <?= $statistics['longest_rental'] ?? 0 ?> dni
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
