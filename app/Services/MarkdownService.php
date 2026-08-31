<?php

declare(strict_types=1);

namespace App\Services;

/**
 * 轻量 Markdown 渲染（无第三方依赖）
 *
 * 支持：标题 / 粗体 / 斜体 / 行内代码 / 代码块(fenced) / 有序·无序列表 /
 * 引用 / 链接 / 图片 / 表格 / 分隔线 / 换行。
 * 输出前做 HTML 转义，仅信任解析出的结构化标签，防 XSS。
 */
class MarkdownService
{
    public static function render(string $text): string
    {
        // 1. 规范化换行
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // 2. 提取代码块（占位符保护，避免被后续正则破坏）
        $blocks = [];
        $text = preg_replace_callback('/```(\w*)\n?(.*?)```/s', function ($m) use (&$blocks) {
            $blocks[] = '<pre><code>'.htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8').'</code></pre>';
            return "\x00BLOCK".(count($blocks) - 1)."\x00";
        }, $text) ?? $text;

        // 3. 转义其余 HTML
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // 4. 行内样式：粗体 / 斜体 / 行内代码 / 链接 / 图片
        $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text) ?? $text;
        $text = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $text) ?? $text;
        $text = preg_replace('/`(.+?)`/s', '<code>$1</code>', $text) ?? $text;
        $text = preg_replace('/!\[([^\]]*)\]\(([^)\s]+)\)/', '<img src="$2" alt="$1" class="rounded-lg max-w-full">', $text) ?? $text;
        $text = preg_replace('/\[([^\]]+)\]\(([^)\s]+)\)/', '<a href="$2" target="_blank" rel="noopener">$1</a>', $text) ?? $text;

        // 5. 行级：标题 / 分隔线
        $lines = explode("\n", $text);
        $out = [];
        $listType = null; // ul | ol
        $inTable = false;

        foreach ($lines as $line) {
            $trimmed = rtrim($line);

            // 表格
            if (preg_match('/^\|(.+)\|$/', $trimmed, $tm)) {
                $cells = array_map('trim', explode('|', trim($tm[1], '|')));
                if ($inTable && preg_match('/^:?-{2,}:?$/', $cells[0] ?? '') && count($cells) > 1 && str_contains($trimmed, '-')) {
                    // 分隔行，跳过
                    continue;
                }
                if (! $inTable) {
                    $out[] = '<div class="overflow-x-auto"><table class="min-w-full text-sm"><tbody>';
                    $inTable = true;
                }
                $out[] = '<tr>'.implode('', array_map(fn ($c) => '<td class="border border-gray-200 dark:border-gray-800 px-3 py-1.5">'.$c.'</td>', $cells)).'</tr>';
                continue;
            }
            if ($inTable) {
                $out[] = '</tbody></table></div>';
                $inTable = false;
            }

            // 代码块占位还原
            if (preg_match('/^\x00BLOCK(\d+)\x00$/', $trimmed, $bm)) {
                $out[] = $blocks[(int) $bm[1]];
                continue;
            }

            // 标题
            if (preg_match('/^(#{1,6})\s+(.+)$/', $trimmed, $hm)) {
                $level = strlen($hm[1]);
                $out[] = '<h'.$level.' class="font-bold text-gray-900 dark:text-white mt-6 mb-3'.($level <= 2 ? ' text-xl' : ($level === 3 ? ' text-lg' : ' text-base')).'">'.$hm[2].'</h'.$level.'>';
                continue;
            }

            // 分隔线
            if (preg_match('/^\s*([-*_])\1{2,}\s*$/', $trimmed)) {
                $out[] = '<hr class="my-6 border-gray-200 dark:border-gray-800">';
                continue;
            }

            // 列表
            if (preg_match('/^\s*[-*+]\s+(.+)$/', $trimmed, $lm)) {
                if ($listType !== 'ul') {
                    if ($listType !== null) {
                        $out[] = '</'.($listType === 'ul' ? 'ul' : 'ol').'>';
                    }
                    $out[] = '<ul class="list-disc pl-6 my-3 space-y-1">';
                    $listType = 'ul';
                }
                $out[] = '<li>'.$lm[1].'</li>';
                continue;
            }
            if (preg_match('/^\s*\d+[.)]\s+(.+)$/', $trimmed, $om)) {
                if ($listType !== 'ol') {
                    if ($listType !== null) {
                        $out[] = '</'.($listType === 'ul' ? 'ul' : 'ol').'>';
                    }
                    $out[] = '<ol class="list-decimal pl-6 my-3 space-y-1">';
                    $listType = 'ol';
                }
                $out[] = '<li>'.$om[1].'</li>';
                continue;
            }
            if ($listType !== null) {
                $out[] = '</'.($listType === 'ul' ? 'ul' : 'ol').'>';
                $listType = null;
            }

            // 引用
            if (preg_match('/^&gt;\s?(.*)$/', $trimmed, $qm)) {
                $out[] = '<blockquote class="border-l-4 border-indigo-200 dark:border-indigo-500/40 pl-4 my-3 text-gray-600 dark:text-gray-400">'.$qm[1].'</blockquote>';
                continue;
            }

            // 空行
            if ($trimmed === '') {
                $out[] = '';
                continue;
            }

            // 普通段落（连续非空行合并为一段）
            $out[] = '<p class="my-2 leading-relaxed">'.$trimmed.'</p>';
        }

        if ($listType !== null) {
            $out[] = '</'.($listType === 'ul' ? 'ul' : 'ol').'>';
        }
        if ($inTable) {
            $out[] = '</tbody></table></div>';
        }

        $html = implode("\n", $out);

        // 合并相邻段落为一个 <p>（保持可读性）
        $html = preg_replace('/<\/p>\n<p class="my-2 leading-relaxed">/', "<br>\n", $html) ?? $html;

        return $html;
    }
}
