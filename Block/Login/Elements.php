<?php
/**
 *
 *
 *
 *
 */

namespace Cotya\Authentication\Block\Login;

use Magento\Framework\View\Element\AbstractBlock;

class Elements extends AbstractBlock
{

    protected function _toHtml()
    {
        $html = '';
        $githubLoginUrl = $this->getUrl('cotya_authentication/index/login');
        $html .= '<a href="'.$githubLoginUrl.'">Login via Github</a>';
        return $html;
    }
}
