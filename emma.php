<?php
namespace Emma;

class Emma
{
  private $emails = [];

  private const CSV_TIMESTAMP_INDEX = 3;
  private const CSV_SEGMENT_SIZE_INDEX = 5;
  private const CSV_OPEN_RATE_INDEX = 16;
  private const CSV_CLICK_THROUGH_RATE_INDEX = 20;

  public function __construct(string $csv_path)
  {
    $this->loadEmailsFromCsv($csv_path);
  }

  private function loadEmailsFromCsv(string $csv_path)
  {
    $file = fopen($csv_path, 'r');

    while (($email = fgetcsv($file)) !== false) {
      $timestamp = strtotime($email[self::CSV_TIMESTAMP_INDEX]);
      $day_of_week = date('w', $timestamp);
      $hour_of_day = date('G', $timestamp);

      $this->emails[] = [
        'segment_size' => (int) $email[self::CSV_SEGMENT_SIZE_INDEX],
        'day_of_week' => $day_of_week,
        'hour_of_day' => $hour_of_day,
        'open_rate' => (float) str_replace(' %', '', $email[self::CSV_OPEN_RATE_INDEX]),
        'click_through_rate' => (float) str_replace(' %', '', $email[self::CSV_CLICK_THROUGH_RATE_INDEX])
      ];
    }
  }

  public function predict(int $segment_size, int $day_of_week, int $hour_of_day): array
  {
    $nearest_neighbor = $this->getNearestNeighbor($segment_size, $day_of_week, $hour_of_day);

    return [
      $nearest_neighbor['open_rate'],
      $nearest_neighbor['click_through_rate']
    ];
  }

  private function getNearestNeighbor($segment_size, $day_of_week, $hour_of_day): array
  {
    $nearest_distance_so_far = null;
    $nearest_neighbor = null;

    $test_email = [
      $segment_size,
      $day_of_week,
      $hour_of_day
    ];

    foreach ($this->emails as $email) {
      $compare = [
        $email['segment_size'],
        $email['day_of_week'],
        $email['click_through_rate']
      ];

      $distance = $this->calculateEuclideanDistance($test_email, $compare);

      if (empty($nearest_distance_so_far) || $distance <= $nearest_distance_so_far) {
        $nearest_distance_so_far = $distance;
        $nearest_neighbor = $email;
      }
    }

    return $nearest_neighbor;
  }

  private function calculateEuclideanDistance($a, $b): float
  {
    $n = count($a);
    $sum = 0;
    for ($i = 0; $i < $n; $i++) {
        $sum += ($a[$i] - $b[$i]) * ($a[$i] - $b[$i]);
    }
    return sqrt($sum);
  }
}