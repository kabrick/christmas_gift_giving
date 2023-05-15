<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class HomeController extends Controller {

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        //
    }

    /**
     * @param $employee_id
     * @return JsonResponse
     */
    public function get_employee_gift($employee_id): JsonResponse {
        // use lock file to ensure queueing so gifts are not duplicated
        $lock_file = fopen(base_path('public/lock/file.lock'), 'w');

        // lock the file for the duration of gift selection
        if (flock($lock_file, LOCK_EX)) {
            // select a gift for our employee
            $select_gift_response = $this->select_gift($employee_id);

            $message = $select_gift_response[0];
            $status_code = $select_gift_response[1];

            // release the file lock
            flock($lock_file, LOCK_UN);
        } else {
            // Lock acquisition failed, handle the situation accordingly
            $message = "Queue is still full, please wait for a moment";
            $status_code = 400;
        }

        fclose($lock_file);

        return response()->json($message, $status_code);
    }

    public function select_gift($employee_id): array {
        try {
            $employees = json_decode(file_get_contents(base_path('public/json_files/clean_employees.json')), true);
            $gifts = json_decode(file_get_contents(base_path('public/json_files/clean_gifts.json')), true);
            $available_gifts = json_decode(file_get_contents(base_path('public/json_files/available_gifts.json')), true);
            $received_gifts = json_decode(file_get_contents(base_path('public/json_files/received_gifts.json')), true);
        } catch (\Exception $e) {
            return ["We know you are excited but Santa is still busy organizing the gifts, please try again later!", 400];
        }

        // check if this is a valid employee
        if (!isset($employees[$employee_id])) {
            return ["Please enter a valid employee ID!", 400];
        }

        // check if the person has already received a gift
        if (isset($received_gifts[$employee_id])) {
            return ["You already received " . $received_gifts[$employee_id], 200];
        }

        // go through their interests searching for available gifts
        foreach ($employees[$employee_id] as $interest) {
            $recommended_gifts = $gifts[$interest] ?? false;

            if ($recommended_gifts) {
                // get the first recommended gift if it is available
                $ideal_gift = array_values(array_intersect($recommended_gifts, $available_gifts))[0] ?? false;

                // if the ideal gift is found,
                if ($ideal_gift) {
                    // remove it from the available gifts,
                    $available_gifts = array_values(array_diff($available_gifts, [$ideal_gift]));

                    // mark the user as having received a gift,
                    $received_gifts[$employee_id] = $ideal_gift;

                    // store the updated values,
                    file_put_contents(base_path('public/json_files/available_gifts.json'), json_encode($available_gifts));
                    file_put_contents(base_path('public/json_files/received_gifts.json'), json_encode($received_gifts));

                    // and return the gift name
                    return [$ideal_gift, 200];
                }
            }
        }

        // at this point we have gone through all their interests and no gift is available
        return ["Unfortunately, we could not find a gift for you.", 200];
    }

    /**
     * Organise data into simpler format. This will reduce complexity when searching for employee gifts
     *
     * Employee names will be keys and the values will be their interests
     *
     * Available gifts are ordered by interests so they can be fetched quickly instead of going through a list of gifts
     * looking for those with our category of interest
     *
     * @return JsonResponse
     */
    public function organise_data() {
        try {
            $employees = json_decode(file_get_contents(base_path('public/json_files/employees.json')), true);
            $gifts = json_decode(file_get_contents(base_path('public/json_files/gifts.json')), true);
        } catch (\Exception $e) {
            return response()->json("Please make sure the employees and gifts files are available", 400);
        }

        $clean_employees = [];
        $clean_gifts = [];
        $available_gifts = [];

        // use the names as keys and interests as values
        foreach ($employees as $employee) {
            $clean_employees[$employee['name']] = $employee['interests'];
        }

        foreach ($gifts as $gift) {
            // set initially available gifts
            $available_gifts[] = $gift['name'];

            // organize the gifts according to the categories
            foreach ($gift['categories'] as $category) {
                $clean_gifts[$category][] = $gift['name'];
            }
        }

        try {
            // save the clean data into files
            file_put_contents(base_path('public/json_files/clean_employees.json'), json_encode($clean_employees));
            file_put_contents(base_path('public/json_files/clean_gifts.json'), json_encode($clean_gifts));
            file_put_contents(base_path('public/json_files/available_gifts.json'), json_encode($available_gifts));
            file_put_contents(base_path('public/json_files/received_gifts.json'), json_encode([]));
        } catch (\Exception $e) {
            return response()->json("An error was encountered while creating files!", 400);
        }

        return response()->json("Gift data has been successfully organized!", 200);
    }
}
