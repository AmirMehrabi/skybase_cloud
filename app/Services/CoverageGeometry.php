<?php

namespace App\Services;

class CoverageGeometry
{
    public function valid(array $geometry): bool
    {
        if (! in_array($geometry['type'] ?? '', ['Polygon', 'MultiPolygon'], true)) {
            return false;
        }
        $polygons = ($geometry['type'] === 'Polygon') ? [$geometry['coordinates'] ?? []] : ($geometry['coordinates'] ?? []);
        if (! is_array($polygons) || ! array_is_list($polygons) || count($polygons) < 1 || count($polygons) > 50 || strlen(json_encode($geometry)) > 200000) {
            return false;
        }
        foreach ($polygons as $rings) {
            if (! is_array($rings) || ! array_is_list($rings) || count($rings) < 1 || count($rings) > 30) {
                return false;
            }
            foreach ($rings as $index => $ring) {
                if (! is_array($ring) || ! array_is_list($ring) || count($ring) < 4 || count($ring) > 500 || $ring[0] != $ring[count($ring) - 1]) {
                    return false;
                }
                foreach ($ring as $point) {
                    if (! is_array($point) || ! array_is_list($point) || count($point) !== 2 || ! is_numeric($point[0]) || ! is_numeric($point[1]) || abs($point[0]) > 180 || abs($point[1]) > 90) {
                        return false;
                    }
                }
                $area = 0;
                $n = count($ring) - 1;
                for ($i = 0; $i < $n; $i++) {
                    $area += $ring[$i][0] * $ring[$i + 1][1] - $ring[$i + 1][0] * $ring[$i][1];
                    for ($j = $i + 1; $j < $n; $j++) {
                        if ($j === $i + 1 || ($i === 0 && $j === $n - 1)) {
                            continue;
                        } if ($this->intersects($ring[$i], $ring[$i + 1], $ring[$j], $ring[$j + 1])) {
                            return false;
                        }
                    }
                }
                if (abs($area) < 1e-12) {
                    return false;
                }
                if ($index > 0) {
                    foreach (array_slice($ring, 0, -1) as $point) {
                        if (! $this->inside($point, $rings[0])) {
                            return false;
                        }
                    }
                    foreach (array_slice($rings, 0, $index) as $previous) {
                        for ($i = 0; $i < $n; $i++) {
                            for ($j = 0; $j < count($previous) - 1; $j++) {
                                if ($this->intersects($ring[$i], $ring[$i + 1], $previous[$j], $previous[$j + 1])) {
                                    return false;
                                }
                            }
                        }
                    }
                    for ($j = 1; $j < $index; $j++) {
                        if ($this->inside($ring[0], $rings[$j]) || $this->inside($rings[$j][0], $ring)) {
                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }

    private function inside(array $p, array $ring): bool
    {
        $inside = false;
        for ($i = 0,$j = count($ring) - 1; $i < count($ring); $j = $i++) {
            [$x,$y] = $ring[$i];
            [$xx,$yy] = $ring[$j];
            if (($y > $p[1]) !== ($yy > $p[1]) && $p[0] < ($xx - $x) * ($p[1] - $y) / ($yy - $y) + $x) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    private function intersects(array $a, array $b, array $c, array $d): bool
    {
        $cross = fn ($p, $q, $r) => ($q[0] - $p[0]) * ($r[1] - $p[1]) - ($q[1] - $p[1]) * ($r[0] - $p[0]);
        $u = $cross($a, $b, $c);
        $v = $cross($a, $b, $d);
        $w = $cross($c, $d, $a);
        $z = $cross($c, $d, $b);

        return $u * $v <= 0 && $w * $z <= 0 && max(min($a[0], $b[0]), min($c[0], $d[0])) <= min(max($a[0], $b[0]), max($c[0], $d[0])) && max(min($a[1], $b[1]), min($c[1], $d[1])) <= min(max($a[1], $b[1]), max($c[1], $d[1]));
    }
}
