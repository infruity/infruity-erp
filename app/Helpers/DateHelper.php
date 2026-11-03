<?php
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

if (!function_exists('dateindo')) {
    function dateindo($tanggal)
    {
        $date = date("d-m-Y", strtotime($tanggal));

        $bulan = array(
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        );

        $pecahkan = explode('-', $date);

        return $pecahkan[0] . ' ' . $bulan[(int) $pecahkan[1]] . ' ' . $pecahkan[2];
    }
}

if (!function_exists('dateEnglish')) {
    function dateEnglish($date, $shortMonth = false)
    {
        // Pastikan tanggal valid
        if (!$date || !strtotime($date))
            return null;

        // Format bulan pendek atau panjang
        $format = $shortMonth ? 'M d, Y' : 'F d, Y';

        return date($format, strtotime($date));
    }
}

if (!function_exists('getParamsUrl')) {
    function getParamsUrl($params)
    {
        $par = null;
        $i = 0;
        foreach ($params as $key => $value) {
            if ($i == 0) {
                $par .= "?" . $key . "=" . $value;
            } else {
                $par .= "&" . $key . "=" . $value;
            }
            $i++;
        }
        return $par;
    }
}

if (!function_exists('daydate')) {
    function daydate($tanggal)
    {
        $date = strtolower(date_format(date_create($tanggal), "D"));
        //dd($date);
        $hari = "";
        if ($date == "sun") {
            $hari = "Minggu";
        } elseif ($date == "mon") {
            $hari = "Senin";
        } elseif ($date == "tue") {
            $hari = "Selasa";
        } elseif ($date == "wed") {
            $hari = "Rabu";
        } elseif ($date == "thu") {
            $hari = "Kamis";
        } elseif ($date == "fri") {
            $hari = "Jum'at";
        } elseif ($date == "sat") {
            $hari = "Sabtu";
        } else {
            $hari = "tidak valid";
        }

        return $hari;
    }
}
if (!function_exists('terbilang')) {

    function penyebut($nilai)
    {
        $nilai = abs($nilai);
        $huruf = array("", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas");
        $temp = "";
        if ($nilai < 12) {
            $temp = " " . $huruf[$nilai];
        } else if ($nilai < 20) {
            $temp = penyebut($nilai - 10) . " Belas";
        } else if ($nilai < 100) {
            $temp = penyebut($nilai / 10) . " Puluh" . penyebut($nilai % 10);
        } else if ($nilai < 200) {
            $temp = " Seratus" . penyebut($nilai - 100);
        } else if ($nilai < 1000) {
            $temp = penyebut($nilai / 100) . " Ratus" . penyebut($nilai % 100);
        } else if ($nilai < 2000) {
            $temp = " Seribu" . penyebut($nilai - 1000);
        } else if ($nilai < 1000000) {
            $temp = penyebut($nilai / 1000) . " Ribu" . penyebut($nilai % 1000);
        } else if ($nilai < 1000000000) {
            $temp = penyebut($nilai / 1000000) . " Juta" . penyebut($nilai % 1000000);
        } else if ($nilai < 1000000000000) {
            $temp = penyebut($nilai / 1000000000) . " Milyar" . penyebut(fmod($nilai, 1000000000));
        } else if ($nilai < 1000000000000000) {
            $temp = penyebut($nilai / 1000000000000) . " Trilyun" . penyebut(fmod($nilai, 1000000000000));
        }
        return $temp;
    }

    function terbilang($nilai)
    {
        if ($nilai < 0) {
            $hasil = "minus " . trim(penyebut($nilai));
        } else {
            $hasil = trim(penyebut($nilai));
        }
        return $hasil;
    }

}

if (!function_exists('tonumber')) {
    function tonumber($data)
    {

        $data = number_format($data, 2, ',', '.');
        return $data;

    }
}

if (!function_exists('tonumberround')) {
    function tonumberround($data)
    {

        $data = number_format($data, 0, ',', '.');
        return $data;

    }
}

if (!function_exists('tanggalMerah')) {
    //fungsi check tanggal merah
    function tanggalMerah($value)
    {
        $tgl = date("Ymd", strtotime($value));
        $tgl1 = date("Y-m-d", strtotime($value));
        //default time zone
        date_default_timezone_set("Asia/Jakarta");
        $array = json_decode(file_get_contents("https://raw.githubusercontent.com/guangrei/Json-Indonesia-holidays/master/calendar.json"), true);
        $d_tgl = false;
        if (isset($array[$tgl])) {
            $d_tgl = true;
        } elseif (date("D", strtotime($tgl1)) === "Sun") {
            $d_tgl = true;
        } else {
            $d_tgl = false;
        }

        return $d_tgl;
    }
}

if (!function_exists('toMinutes')) {
    function toMinutes($str)
    {
        $jam = (float) date("H", strtotime($str)) * 60;
        $minute = (float) date("i", strtotime($str));
        $second = (float) date("s", strtotime($str)) / 60;

        $total = $jam + $minute + $second;

        return $total;
    }
}

if (!function_exists('toHours')) {
    function toHours($minutes)
    {
        $hours = ($minutes - floor($minutes / 60) * 60);

        return $hours;
    }
}

if (!function_exists('detect_chat_id')) {
    /**
     * Normalize chat id.
     *
     * @param $chatId
     * @return string
     */
    function detect_chat_id($chatId)
    {
        $chatId = str_replace([' ', '+'], '', $chatId ?? '');
        /*if (strpos($chatId, '-') !== false) {
        if (!(strpos($chatId, '@g.us') !== false)) {
        $chatId .= '@g.us';
        }
        } else if (!(strpos($chatId, '@c.us') !== false)) {
        $chatId = preg_replace('/^08/', '628', $chatId);
        $chatId .= '@c.us';
        }*/

        if (!(strpos($chatId, '@c.us') !== false) && (preg_match('/^08/', $chatId) || preg_match('/^628/', $chatId)) && strlen($chatId) <= 15) {
            $chatId = preg_replace('/^08/', '628', $chatId);
            // $chatId .= '@c.us';
        } else if (!(strpos($chatId, '@g.us') !== false) && !(strpos($chatId, '@c.us') !== false)) {
            // $chatId .= '@g.us';
        }

        return $chatId;
    }
}

if (!function_exists('change_form')) {
    function change_form($teks)
    {
        $teks = preg_replace('/[^\w\s_]/', '', $teks);
        $teks = str_replace(' ', '_', $teks);
        $teks = strtolower($teks);
        return $teks;
    }
}

if (!function_exists('my_encrypt')) {
    function my_encrypt($data)
    {
        $key = substr(config('app.key'), 0, 16); // Ambil 16 karakter pertama APP_KEY
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-128-CTR', $key, 0, $iv);
        return Str::substr(base64_encode($iv . $encrypted), 0, 20); // Potong ke 20 karakter
    }
}

if (!function_exists('decrypt')) {
    function my_decrypt($encrypted)
    {
        $key = substr(config('app.key'), 0, 16);
        $data = base64_decode($encrypted);
        $iv = substr($data, 0, 16);
        $ciphertext = substr($data, 16);
        return openssl_decrypt($ciphertext, 'AES-128-CTR', $key, 0, $iv);
    }
}

if (!function_exists('formatRibuanToK')) {
    function formatRibuanToK($number)
    {
        // Pastikan input numerik
        $number = floatval(preg_replace('/[^\d.-]/', '', $number));

        if ($number >= 1000) {
            // Jika kelipatan 1000 pas, tidak tampil desimal
            if ($number % 1000 == 0) {
                return ($number / 1000) . 'K';
            } else {
                return number_format($number / 1000, 1) . 'K';
            }
        }

        return (string) $number;
    }
}

