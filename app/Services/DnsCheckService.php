<?php

namespace App\Services;

use App\Models\DnsCheck;
use Symfony\Component\Process\Process;

class DnsCheckService
{
    public function __construct(private readonly InputValidator $validator)
    {
    }

    public function check(string $domain): DnsCheck
    {
        $domain = $this->validator->domain($domain);
        $mx = $this->dig(['MX', $domain]);
        $txt = $this->dig(['TXT', $domain]);
        $spf = collect(explode("\n", $txt))->filter(fn ($line) => str_contains(strtolower($line), 'v=spf1'))->implode("\n");
        $dmarc = $this->dig(['TXT', '_dmarc.'.$domain]);
        $dkimMail = $this->dig(['TXT', 'mail._domainkey.'.$domain]);
        $dkimDefault = $this->dig(['TXT', 'default._domainkey.'.$domain]);
        $a = $this->dig(['A', 'mail.'.$domain]);
        $firstIp = trim(strtok($a, "\n") ?: '');
        $ptr = filter_var($firstIp, FILTER_VALIDATE_IP) ? $this->dig(['-x', $firstIp]) : '';

        $score = 0;
        $score += trim($mx) !== '' ? 20 : 0;
        $score += str_contains(strtolower($spf), 'v=spf1') ? 20 : 0;
        $score += trim($dkimMail.$dkimDefault) !== '' ? 20 : 0;
        $score += str_contains(strtolower($dmarc), 'v=dmarc1') ? 20 : 0;
        $score += trim($ptr) !== '' ? 20 : 0;

        return DnsCheck::create([
            'domain' => $domain,
            'mx_result' => $mx,
            'spf_result' => $spf,
            'dkim_mail_result' => $dkimMail,
            'dkim_default_result' => $dkimDefault,
            'dmarc_result' => $dmarc,
            'a_result' => $a,
            'ptr_result' => $ptr,
            'score' => $score,
            'checked_at' => now(),
        ]);
    }

    private function dig(array $args): string
    {
        $process = new Process(array_merge(['dig', '+short'], $args));
        $process->setTimeout(12);
        $process->run();
        return trim($process->getOutput().$process->getErrorOutput());
    }
}
