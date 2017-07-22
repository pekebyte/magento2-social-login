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
    /**
     * Check wether the current user is Logged in or not
     * @return boolean
     */
    public function isUserLoggedIn()
    {
        return $this->customerSession->isLoggedIn();
    }
    /**
     * Check if Facebook Login has been enabled by the administrator
     * @return boolean
     */
    public function isFacebookEnabled()
    {
        return $this->hlp->getConfigValue('pekebyte_social_login/facebook/enabled');
    }
    /**
     * Returns the Facebook App Id entered on the config
     * @return string
     */
    public function getFBAppId()
    {
        return $this->hlp->getConfigValue('pekebyte_social_login/facebook/api_key');
    }
    /**
     * Returns the Facebook App Secret entered on the config
     * @return string
     */
    public function getFBAppSecret()
    {
        return $this->hlp->getConfigValue('pekebyte_social_login/facebook/api_secret');
    }
    /**
     * Check if Google Login has been enabled by the administrator
     * @return boolean
     */
    public function isGoogleEnabled()
    {
        return $this->hlp->getConfigValue('pekebyte_social_login/google/enabled');
    }
    /**
     * Returns the Google App Id entered on the config
     * @return string
     */
    public function getGoogleAppId()
    {
        return $this->hlp->getConfigValue('pekebyte_social_login/google/app_id');
    }
    /**
     * Returns the Google Api Key entered on the config
     * @return string
     */
    public function getGoogleApiKey()
    {
        return $this->hlp->getConfigValue('pekebyte_social_login/google/api_key');
    }
}
