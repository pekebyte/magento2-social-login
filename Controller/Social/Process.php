<?php

namespace Pekebyte\SocialLogin\Controller\Social;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Account\Redirect as AccountRedirect;
use Magento\Framework\App\Config\ScopeConfigInterface;

use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Exception\InputException;

class Process extends \Magento\Framework\App\Action\Action
{

    protected $storeManager;

    protected $customerFactory;

    protected $_customersCollection;

    protected $session;

    protected $escaper;

    private $cookieMetadataFactory;

    private $cookieMetadataManager;

    protected $accountRedirect;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customersCollection,
        Session $customerSession,
        AccountRedirect $accountRedirect,
        ScopeConfigInterface $scopeConfig,
        Escaper $escaper
    ) {
        $this->storeManager     = $storeManager;
        $this->customerFactory  = $customerFactory;
        $this->_customersCollection = $customersCollection;
        $this->session = $customerSession;
        $this->scopeConfig = $scopeConfig;
        $this->escaper = $escaper;
        $this->accountRedirect = $accountRedirect;
        parent::__construct($context);
    }

    /**
     * Retrieve cookie manager
     *
     * @deprecated
     * @return \Magento\Framework\Stdlib\Cookie\PhpCookieManager
     */
    private function getCookieManager()
    {
        if (!$this->cookieMetadataManager) {
            $this->cookieMetadataManager = \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\Stdlib\Cookie\PhpCookieManager::class
            );
        }
        return $this->cookieMetadataManager;
    }

    /**
     * Get scope config
     *
     * @return ScopeConfigInterface
     * @deprecated
     */
    private function getScopeConfig()
    {
        if (!($this->scopeConfig instanceof \Magento\Framework\App\Config\ScopeConfigInterface)) {
            return \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\App\Config\ScopeConfigInterface::class
            );
        } else {
            return $this->scopeConfig;
        }
    }

    /**
     * Retrieve cookie metadata factory
     *
     * @deprecated
     * @return \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    private function getCookieMetadataFactory()
    {
        if (!$this->cookieMetadataFactory) {
            $this->cookieMetadataFactory = \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory::class
            );
        }
        return $this->cookieMetadataFactory;
    }
    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($this->getRequest()->getPost()->count() > 0) {
            //Assigning data
            $data = $this->getRequest()->getPostValue();
            //Social Validation
            $sfid = null;
            if (isset($data['fbid'])){
                $sfid = ['fbid','Facebook'];
            }
            if (isset($data['googid'])){
                $sfid = ['googid','Google'];
            }
            if ($sfid[0]){
                $search = $this->_customersCollection->create()
                            ->addAttributeToSelect('*')
                            ->addAttributeToFilter($sfid[0],$data[$sfid[0]])
                            ->getFirstItem();
                $customer_id = (int) $search->getId();
                if ($customer_id){
                    $this->session->loginById($customer_id);
                    return $this->handleredirect();
                }
                else{
                    $search = $this->_customersCollection->create()
                            ->addAttributeToSelect('*')
                            ->addAttributeToFilter('email',$data['email'])
                            ->getFirstItem();
                    $customer_id = (int) $search->getId();
                    if ($customer_id){
                        $customer = $this->customerFactory->create()->load($customer_id);
                        $customer->setData($sfid[0],$data[$sfid[0]]);
                        
                        try{
                            $customer->save();
                            $this->session->loginById($customer->getId());
                            $this->messageManager->addSuccess(__('Your %social account is now Linked with your account',['social' => $sfid[1]]));
                            return $this->handleredirect();
                        }
                        catch (\Exception $e){
                            $this->messageManager->addError($this->escaper->escapeHtml($e->getMessage()));
                            return $resultRedirect->setPath('customer/account/login'); 
                        }
                    }
                }
            }
            //Generating random password
            $password = bin2hex(openssl_random_pseudo_bytes(4));
            // Get Website ID
            $websiteId  = $this->storeManager->getWebsite()->getWebsiteId();
            // Instantiate object (this is the most important part)
            $customer   = $this->customerFactory->create();
            $customer->setWebsiteId($websiteId);
            $customer->addData($data);
            $customer->setPassword($password);
            
            try{
                // Save data
                $customer->save();
                $this->_eventManager->dispatch(
                    'customer_register_success',
                    ['account_controller' => $this, 'customer' => $customer]
                );
                $customer->sendNewAccountEmail();
                $this->session->loginById($customer->getId());
                return $this->handleredirect();
            }
            catch (StateException $e) {
                $url = $this->urlModel->getUrl('customer/account/forgotpassword');
                // @codingStandardsIgnoreStart
                $message = __(
                    'There is already an account with this email address. If you are sure that it is your email address, <a href="%1">click here</a> to get your password and access your account.',
                    $url
                );
                // @codingStandardsIgnoreEnd
                $this->messageManager->addError($message);
            }
            catch (InputException $e) {
                $this->messageManager->addError($this->escaper->escapeHtml($e->getMessage()));
                foreach ($e->getErrors() as $error) {
                    $this->messageManager->addError($this->escaper->escapeHtml($error->getMessage()));
                }
            }
            catch (LocalizedException $e) {
                $this->messageManager->addError($this->escaper->escapeHtml($e->getMessage()));
            }
            return $resultRedirect->setPath('customer/account/login'); 
        }
        else{
            $this->messageManager->addError(__('You attempted to do a forbidden action!.'));
            return $resultRedirect->setPath('/');
        }
        
    }

    private function handleredirect(){
        if ($this->getCookieManager()->getCookie('mage-cache-sessid')) {
            $metadata = $this->getCookieMetadataFactory()->createCookieMetadata();
            $metadata->setPath('/');
            $this->getCookieManager()->deleteCookie('mage-cache-sessid', $metadata);
        }
        $redirectUrl = $this->accountRedirect->getRedirectCookie();
        
        if (!$this->getScopeConfig()->getValue('customer/startup/redirect_dashboard') && $redirectUrl) {
            $this->accountRedirect->clearRedirectCookie();
            $resultRedirect = $this->resultRedirectFactory->create();
            // URL is checked to be internal in $this->_redirect->success()
            $resultRedirect->setUrl($this->_redirect->success($redirectUrl));
            return $resultRedirect;
        }

        return $this->accountRedirect->getRedirect();
    }
}