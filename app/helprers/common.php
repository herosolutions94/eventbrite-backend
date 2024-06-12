<?php
use Illuminate\Support\Facades\Storage;

function pr($data){
    print_r($data);die;
}
function get_site_image_src($path, $image, $type = '', $user_image = false)
{
    $filepath = Storage::url($path.$type.$image);
    $filepath=config('app.url').$filepath;
    if (!empty($image) && @file_exists(".".Storage::url($path.'/'.$type.$image))) {
    // if (!empty($image) && @getimagesize($filepath)) {
        return url($filepath);
    }
    return empty($user_image) ? asset('images/no-image.svg') : asset('images/no-user.svg');
}
function format_amount($amount, $size = 2)
{
    $amount = floatval($amount);
    return $amount >= 0 ? "$".number_format($amount, $size) : "$ (".number_format(abs($amount), $size).')';
}
function format_date($d, $format = '', $default_show = 'TBD')
{
    $format = empty($format) ? 'm/d/Y' : $format;
    // $d = str_replace('/', '-', $d);
    if($d=='0000:00:00' || $d=='0000-00-00' || !$d)
        return $default_show;
    $d = (is_numeric($d) && (int)$d == $d ) ? $d : strtotime($d);
    return date($format, $d);
}
?>