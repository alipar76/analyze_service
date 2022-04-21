<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use QuickChart;

class AnalyzeController extends Controller
{
    public function passengers_statistics(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required',
            'first_airport' => 'required',
            'second_airport' => 'required',
            'start_date' => 'required',
            'end_date' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $username = $request->header('username');
        $token = $request->header('token');
        if ($username == '' || $token == '') {
            return response()->json(['message' => 'Please send username and token.'], 400);
        }

        $check_auth = Http::withHeaders([
            'username' => $username,
            'token' => $token
        ])->post('http://172.17.0.1:7000/api/user/auth');

        $check_auth = json_decode($check_auth);

        if ($check_auth->isvalid != 1) {
            return response()->json([
                'message' => $check_auth->message,
            ]);
        }

        $period = \Carbon\CarbonPeriod::create($request->start_date, '1 month', $request->end_date);
        $labels = array();
        foreach ($period as $dt) {
            array_push($labels, $dt->format("Y-m"));
        }

        $flights = Http::withHeaders([
            'username' => $username,
            'token' => $token
        ])->post('http://172.17.0.1:8000/api/flights/filter', [
            'datetime' => [$request->start_date, $request->end_date],
        ]);

        for ($i = 0; $i < count($labels); $i++) {
            $first_airport_data[$labels[$i]] = 0;
            $second_airport_data[$labels[$i]] = 0;
        }
        if ($request->type == 'in') {

            foreach (json_decode($flights) as $flight) {

                if ($flight->dest_airport == $request->first_airport) {
                    $datetime = Carbon::createFromDate($flight->datetime)->format('Y-m');
                    for ($i = 0; $i < count($labels); $i++) {
                        if ($datetime == $labels[$i]) {
                            $first_airport_data[$labels[$i]]++;
                        }
                    }
                } elseif ($flight->dest_airport == $request->second_airport) {
                    $datetime = Carbon::createFromDate($flight->datetime)->format('Y-m');
                    for ($i = 0; $i < count($labels); $i++) {
                        if ($datetime == $labels[$i]) {
                            $second_airport_data[$labels[$i]]++;
                        }
                    }
                }

            }

        } elseif ($request->type == 'out') {

            foreach (json_decode($flights) as $flight) {

                if ($flight->origin_airport == $request->first_airport) {
                    $datetime = Carbon::createFromDate($flight->datetime)->format('Y-m');
                    for ($i = 0; $i < count($labels); $i++) {
                        if ($datetime == $labels[$i]) {
                            $first_airport_data[$labels[$i]]++;
                        }
                    }
                } elseif ($flight->origin_airport == $request->second_airport) {
                    $datetime = Carbon::createFromDate($flight->datetime)->format('Y-m');
                    for ($i = 0; $i < count($labels); $i++) {
                        if ($datetime == $labels[$i]) {
                            $second_airport_data[$labels[$i]]++;
                        }
                    }
                }

            }

        } else {
            return response()->json('Invalid sent data', 400);
        }

        $chart = new QuickChart(array(
            'width' => 700,
            'height' => 400
        ));

        $chart->setConfig('{
          type: "bar",
          data: {
            labels: ' . json_encode($labels) . ',
            datasets: [{
              label: ' . json_encode($request->first_airport) . ',
              data: ' . json_encode(array_values($first_airport_data)) . '
            },{
              label: ' . json_encode($request->second_airport) . ',
              data: ' . json_encode(array_values($second_airport_data)) . '
            }]
          },
          options: {
            title: {
              display: true,
              text: "Passengers of these airports between ' . $request->start_date . ' and ' . $request->end_date . '"
            }
          }
        }');

        $url = $chart->getUrl();

        return response()->json(['chartUrl' => $url], 200);
    }

    public function carriers_statistics(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_carrier' => 'required',
            'second_carrier' => 'required',
            'start_date' => 'required',
            'end_date' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $username = $request->header('username');
        $token = $request->header('token');
        if (is_null($username) || is_null($token)) {
            return response()->json(['message' => 'Please send username and token.'], 400);
        }

        $check_auth = Http::withHeaders([
            'username' => $username,
            'token' => $token
        ])->post('http://172.17.0.1:7000/api/user/auth');

        $check_auth = json_decode($check_auth);

        if ($check_auth->isvalid != 1) {
            return response()->json([
                'message' => $check_auth->message,
            ]);
        }

        $period = \Carbon\CarbonPeriod::create($request->start_date, '1 month', $request->end_date);
        $labels = array();
        foreach ($period as $dt) {
            array_push($labels, $dt->format("Y-m"));
        }

        $flights = Http::withHeaders([
            'username' => $username,
            'token' => $token
        ])->post('http://172.17.0.1:8000/api/flights/filter', [
            'datetime' => [$request->start_date, $request->end_date],
        ]);

        for ($i = 0; $i < count($labels); $i++) {
            $first_carrier_data[$labels[$i]] = 0;
            $second_carrier_data[$labels[$i]] = 0;
        }

        foreach (json_decode($flights) as $flight) {

            if ($flight->carrier == $request->first_carrier) {

                $datetime = Carbon::createFromDate($flight->datetime)->format('Y-m');
                for ($i = 0; $i < count($labels); $i++) {
                    if ($datetime == $labels[$i]) {
                        $first_carrier_data[$labels[$i]] += $flight->price;
                    }
                }

            } elseif ($flight->carrier == $request->second_carrier) {

                $datetime = Carbon::createFromDate($flight->datetime)->format('Y-m');
                for ($i = 0; $i < count($labels); $i++) {
                    if ($datetime == $labels[$i]) {
                        $second_carrier_data[$labels[$i]] += $flight->price;
                    }
                }

            }

        }

        $chart = new QuickChart(array(
            'width' => 700,
            'height' => 400
        ));

        $chart->setConfig('{
          type: "bar",
          data: {
            labels: ' . json_encode($labels) . ',
            datasets: [{
              label: ' . json_encode($request->first_carrier) . ',
              data: ' . json_encode(array_values($first_carrier_data)) . '
            },{
              label: ' . json_encode($request->second_carrier) . ',
              data: ' . json_encode(array_values($second_carrier_data)) . '
            }]
          },
          options: {
            title: {
              display: true,
              text: "Total sale of ' . $request->first_carrier . ' and ' . $request->second_carrier . ' between '.$request->start_date.' and '.$request->end_date.'"
            }
          }
        }');

        $url = $chart->getUrl();

        return response()->json(['chartUrl' => $url], 200);
    }

    public function flights_statistics(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_plane_type' => 'required',
            'second_plane_type' => 'required',
            'start_date' => 'required',
            'end_date' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $username = $request->header('username');
        $token = $request->header('token');
        if (is_null($username) || is_null($token)) {
            return response()->json(['message' => 'Please send username and token.'], 400);
        }

        $check_auth = Http::withHeaders([
            'username' => $username,
            'token' => $token
        ])->post('http://172.17.0.1:7000/api/user/auth');

        $check_auth = json_decode($check_auth);

        if ($check_auth->isvalid != 1) {
            return response()->json([
                'message' => $check_auth->message,
            ]);
        }

        $flights = Http::withHeaders([
            'username' => $username,
            'tocken' => $token
        ])->post('http://172.17.0.1:8000/api/flights/filter', [
            'datetime' => [$request->start_date, $request->end_date],
        ]);

        $period = \Carbon\CarbonPeriod::create($request->start_date, '1 month', $request->end_date);
        $labels = array();
        foreach ($period as $dt) {
            array_push($labels, $dt->format("Y-m"));
        }

        $first_plane_type_data = [];
        $second_plane_type_data = [];
        for ($i = 0; $i < count($labels); $i++) {
            $first_plane_type_data[$labels[$i]] = 0;
            $second_plane_type_data[$labels[$i]] = 0;
        }

        foreach (json_decode($flights) as $flight) {

            if ($flight->plane_type == $request->first_plane_type) {

                $datetime = Carbon::createFromDate($flight->datetime)->format('Y-m');
                for ($i = 0; $i < count($labels); $i++) {
                    if ($datetime == $labels[$i]) {
                        $first_plane_type_data[$labels[$i]]++;
                    }
                }

            } elseif ($flight->plane_type == $request->second_plane_type) {

                $datetime = Carbon::createFromDate($flight->datetime)->format('Y-m');
                for ($i = 0; $i < count($labels); $i++) {
                    if ($datetime == $labels[$i]) {
                        $second_plane_type_data[$labels[$i]]++;
                    }
                }

            }

        }

        $chart = new QuickChart(array(
            'width' => 700,
            'height' => 400
        ));

        $chart->setConfig('{
          type: "bar",
          data: {
            labels: ' . json_encode($labels) . ',
            datasets: [{
              label: ' . json_encode($request->first_plane_type) . ',
              data: ' . json_encode(array_values($first_plane_type_data)) . '
            },{
              label: ' . json_encode($request->second_plane_type) . ',
              data: ' . json_encode(array_values($second_plane_type_data)) . '
            }]
          },
          options: {
            title: {
              display: true,
              text: "Flights done by ' . $request->first_plane_type . ' and ' . $request->second_plane_type . ' between ' . $request->start_date . ' and ' . $request->end_date . '"
            }
          }
        }');

        $url = $chart->getUrl();

        return response()->json(['chartUrl' => $url], 200);
    }
}
