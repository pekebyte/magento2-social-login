<?php
namespace Pekebyte\SocialLogin\Block\Frontend;

class SocialLogin extends \Magento\Framework\View\Element\Template
{

    protected $hlp;

    protected $customerSession;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Pekebyte\SocialLogin\Helper\Data $helper,
        \Magento\Customer\Model\Session $customerSession,
        array $data = []
    ) {
        $this->hlp = $helper;
        $this->customerSession = $customerSession;
        parent::__construct($context, $data);
    }
    public function isUserLoggedIn()
    {
        return $this->customerSession->isLoggedIn();
    }
    public function isFacebookEnabled()
    {
        return $this->hlp->getConfigValue('pekebyte_social_login/facebook/enabled');
    }

    public function getFBAppId()
    {
        return $this->hlp->getConfigValue('pekebyte_social_login/facebook/api_key');
    }

    public function getFBAppSecret()
    {
        return $this->hlp->getConfigValue('pekebyte_social_login/facebook/api_secret');
    }

    public function isGoogleEnabled()
    {
        return $this->hlp->getConfigValue('pekebyte_social_login/google/enabled');
    }

    public function getGoogleAppId()
    {
        return $this->hlp->getConfigValue('pekebyte_social_login/google/app_id');
    }

    public function getGoogleApiKey()
    {
        return $this->hlp->getConfigValue('pekebyte_social_login/google/api_key');
    }
}
