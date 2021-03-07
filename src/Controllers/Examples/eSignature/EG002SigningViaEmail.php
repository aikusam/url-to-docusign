<?php
/**
 * Example 002: Remote signer, cc, envelope has three documents
 */

namespace Example\Controllers\Examples\eSignature;

use DocuSign\eSign\Client\ApiException;
use DocuSign\eSign\Model\CarbonCopy;
use DocuSign\eSign\Model\Document;
use DocuSign\eSign\Model\EnvelopeDefinition;
use DocuSign\eSign\Model\Recipients;
use DocuSign\eSign\Model\Signer;
use DocuSign\eSign\Model\SignHere;
use DocuSign\eSign\Model\Text;
use DocuSign\eSign\Model\Tabs;
use Example\Controllers\BaseController;
use Example\Services\SignatureClientService;
use Example\Services\RouterService;

class EG002SigningViaEmail extends BaseController
{
    /** signatureClientService */
    private $clientService;

    /** RouterService */
    private $routerService;

    /** Specific template arguments */
    private $args;

    private $eg = "eg002";  # reference (and URL) for this example

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->args = $this->getTemplateArgs();
        $this->clientService = new SignatureClientService($this->args);
        $this->routerService = new RouterService();
        parent::controller($this->eg, $this->routerService, basename(__FILE__));
    }

    /**
     * 1. Check the token
     * 2. Call the worker method
     * 3. Redirect the user to the signing
     *
     * @return void
     */
    public function createController(): void
    {
        $minimum_buffer_min = 3;
        if ($this->routerService->ds_token_ok($minimum_buffer_min)) {
            # 2. Call the worker method
            # More data validation would be a good idea here
            # Strip anything other than characters listed
            $results = $this->worker($this->args);

            if ($results) {
                $_SESSION["envelope_id"] = $results["envelope_id"]; # Save for use by other examples
                                                                    # which need an envelope_id
                $this->clientService->showDoneTemplate(
                    "Envelope sent",
                    "Envelope sent",
                    "The envelope has been created and sent!<br/> Envelope ID {$results["envelope_id"]}."
                );
            }
        } else {
            $this->clientService->needToReAuth($this->eg);
        }
    }


    /**
     * Do the work of the example
     * 1. Create the envelope request object
     * 2. Send the envelope
     *
     * @param  $args array
     * @return array ['redirect_url']
     * @throws ApiException for API issues and file access/permission issues.
     */
    # ***DS.snippet.0.start
    public function worker($args): array
    {
        # 1. Create the envelope request object
        $envelope_definition = $this->make_envelope($args["envelope_args"]);
        $envelope_api = $this->clientService->getEnvelopeApi();

        # 2. call Envelopes::create API method
        # Exceptions will be caught by the calling function
        try {
            $results = $envelope_api->createEnvelope($args['account_id'], $envelope_definition);
        } catch (ApiException $e) {
            $this->clientService->showErrorTemplate($e);
            exit;
        }

        return ['envelope_id' => $results->getEnvelopeId()];
    }

    /**
     * Creates envelope definition
     * Document 1: An HTML document.
     * Document 2: A Word .docx document.
     * Document 3: A PDF document.
     * DocuSign will convert all of the documents to the PDF format.
     * The recipients' field tags are placed using <b>anchor</b> strings.
     *
     * Parameters for the envelope: signer_email, signer_name, signer_client_id
     *
     * @param  $args array
     * @return EnvelopeDefinition -- returns an envelope definition
     */
    private function make_envelope(array $args): EnvelopeDefinition
    {
        $email = $_GET['email'];
        $fax = $_GET['fax'];
        $BusinessName = $_GET['BusinessName'];
        $Address = $_GET['Address'];
        $city = $_GET['city'];
        $state = $_GET['state'];
        $zip = $_GET['zip'];
        $phone = $_GET['phone'];
        $website = $_GET['website'];
        if(!$email) $email = "";
        if(!$fax) $fax = "";
        if(!$BusinessName) $BusinessName = "";
        if(!$Address) $Address = "";
        if(!$city) $city = "";
        if(!$state) $state = "";
        if(!$zip) $zip = "";
        if(!$phone) $phone = "";
        if(!$website) $website = "";
        $doc_name = 'ORDER FORM.pdf';
        $content_bytes = file_get_contents(self::DEMO_DOCS_PATH . $doc_name);
        $base64_file_content = base64_encode($content_bytes);

        # Create the document model
        $document = new Document([ # create the DocuSign document object
            'document_base64' => $base64_file_content,
            'name' => 'ORDER FORM', # can be different from actual file name
            'file_extension' => 'pdf', # many different document types are accepted
            'document_id' => 1 # a label used to reference the doc
        ]);

        
        # Create the signer recipient model
        $signer = new Signer([
            'email' => $email, 'name' => "Auto made docusign template",
            'recipient_id' => "1", 'routing_order' => "1"]);
        # Create fields using absolute positioning
        # Create a sign_here tab (field on the document)
        $sign_here = new SignHere(['document_id' => '1', 'page_number' => '1',
            'x_position' => '325', 'y_position' => '420']);

        $text_first = new Text(['document_id' => "1", 'page_number' => "1",
            'x_position' => "10", 'y_position' => "50", 'value' => $city.$state.$zip,
            'font' => "helvetica", 'font_size' => "size14", 'tab_label' => "text", 
            'height' => "20", 'width' => "170", 'required' => "false"]);
        $text_verify_1 = new Text(['document_id' => "1", 'page_number' => "1",
            'x_position' => "15", 'y_position' => "128",
            'font' => "helvetica", 'font_size' => "size14", 'tab_label' => "text", 
            'height' => "23", 'width' => "170", 'required' => "false"]);
        $text_verify_2 = new Text(['document_id' => "1", 'page_number' => "1",
            'x_position' => "15", 'y_position' => "151",
            'font' => "helvetica", 'font_size' => "size14", 'tab_label' => "text", 
            'height' => "23", 'width' => "170", 'required' => "false"]);
        $text_verify_3 = new Text(['document_id' => "1", 'page_number' => "1",
            'x_position' => "15", 'y_position' => "174",
            'font' => "helvetica", 'font_size' => "size14", 'tab_label' => "text", 
            'height' => "23", 'width' => "170", 'required' => "false"]);

        $text_return_1 = new Text(['document_id' => "1", 'page_number' => "1",
            'x_position' => "15", 'y_position' => "410",
            'font' => "helvetica", 'font_size' => "size14", 'tab_label' => "text", 'value' => "4 text",
            'height' => "23", 'width' => "170", 'required' => "false"]);
        $text_return_2 = new Text(['document_id' => "1", 'page_number' => "1",
            'x_position' => "15", 'y_position' => "435",
            'font' => "helvetica", 'font_size' => "size14", 'tab_label' => "text", 'value' => "5 text",
            'height' => "23", 'width' => "170", 'required' => "false"]);
        $text_return_3 = new Text(['document_id' => "1", 'page_number' => "1",
            'x_position' => "15", 'y_position' => "460",
            'font' => "helvetica", 'font_size' => "size14", 'tab_label' => "text", 'value' => "6 text",
            'height' => "23", 'width' => "170", 'required' => "false"]);
        $text_phone = new Text(['document_id' => "1", 'page_number' => "1",
            'x_position' => "15", 'y_position' => "520",
            'font' => "helvetica", 'font_size' => "size14", 'tab_label' => "text", 'value' => "6 text",
            'height' => "23", 'width' => "170", 'required' => "false"]);

        $text_print_name = new Text(['document_id' => "1", 'page_number' => "1",
            'x_position' => "306", 'y_position' => "470",
            'font' => "helvetica", 'font_size' => "size14", 'tab_label' => "text", 'value' => "5 text",
            'height' => "23", 'width' => "130", 'required' => "false"]);
        $text_date = new Text(['document_id' => "1", 'page_number' => "1",
            'x_position' => "465", 'y_position' => "470",
            'font' => "helvetica", 'font_size' => "size14", 'tab_label' => "text", 'value' => "6 text",
            'height' => "23", 'width' => "108", 'required' => "false"]);
        $text_business = new Text(['document_id' => "1", 'page_number' => "1",
            'x_position' => "100", 'y_position' => "560",
            'font' => "helvetica", 'font_size' => "size11", 'tab_label' => "text", 'value' => $BusinessName,
            'height' => "15", 'width' => "175", 'required' => "false"]);
        $text_website = new Text(['document_id' => "1", 'page_number' => "1",
            'x_position' => "55", 'y_position' => "581",
            'font' => "helvetica", 'font_size' => "size11", 'tab_label' => "text", 'value' => $website,
            'height' => "15", 'width' => "222", 'required' => "false"]);
        $text_email = new Text(['document_id' => "1", 'page_number' => "1",
            'x_position' => "55", 'y_position' => "603",
            'font' => "helvetica", 'font_size' => "size11", 'tab_label' => "text", 'value' => $email,
            'height' => "15", 'width' => "230", 'required' => "false"]);
        $text_tollfree = new Text(['document_id' => "1", 'page_number' => "1",
            'x_position' => "60", 'y_position' => "625",
            'font' => "helvetica", 'font_size' => "size11", 'tab_label' => "text", 'value' => $fax,
            'height' => "15", 'width' => "240", 'required' => "false"]);

        $text_spec_1 = new Text(['document_id' => "1", 'page_number' => "1",
            'x_position' => "306", 'y_position' => "535",
            'font' => "helvetica", 'font_size' => "size14", 'tab_label' => "text", 'value' => "11 text",
            'height' => "30", 'width' => "272", 'required' => "false"]);
        $text_spec_2 = new Text(['document_id' => "1", 'page_number' => "1",
            'x_position' => "306", 'y_position' => "558",
            'font' => "helvetica", 'font_size' => "size14", 'tab_label' => "text", 'value' => "12 text",
            'height' => "30", 'width' => "272", 'required' => "false"]);
        $text_spec_3 = new Text(['document_id' => "1", 'page_number' => "1",
            'x_position' => "306", 'y_position' => "581",
            'font' => "helvetica", 'font_size' => "size14", 'tab_label' => "text", 'value' => "13 text",
            'height' => "30", 'width' => "272", 'required' => "false"]);
        $text_spec_4 = new Text(['document_id' => "1", 'page_number' => "1",
            'x_position' => "306", 'y_position' => "604",
            'font' => "helvetica", 'font_size' => "size14", 'tab_label' => "text", 'value' => "14 text",
            'height' => "30", 'width' => "272", 'required' => "false"]);

        # Add the tabs model (including the sign_here tab) to the signer
        # The Tabs object wants arrays of the different field/tab types
        $signer->settabs(new Tabs(
            ['text_tabs' => [$text_first,$text_return_1, $text_return_2, $text_return_3, $text_verify_1, $text_verify_2, $text_verify_3, $text_business, $text_website, $text_email, $text_tollfree, $text_spec_1, $text_spec_2, $text_spec_3, $text_spec_4, $text_print_name, $text_date], 'sign_here_tabs' => [$sign_here]]));

        # Next, create the top level envelope definition and populate it.
        $envelope_definition = new EnvelopeDefinition([
            'email_subject' => "Please sign this document sent from the PHP SDK",
            'documents' => [$document],
            # The Recipients object wants arrays for each recipient type
            'recipients' => new Recipients(['signers' => [$signer]]),
            'status' => "sent", # requests that the envelope be created and sent.
        ]);

        return $envelope_definition;
        
    }
    # ***DS.snippet.0.end

    /**
     * Get specific template arguments
     *
     * @return array
     */
    private function getTemplateArgs(): array
    {
        $signer_name  = preg_replace('/([^\w \-\@\.\,])+/', '', $_POST['signer_name' ]);
        $signer_email = preg_replace('/([^\w \-\@\.\,])+/', '', $_POST['signer_email']);
        $cc_name      = preg_replace('/([^\w \-\@\.\,])+/', '', $_POST['cc_name'     ]);
        $cc_email     = preg_replace('/([^\w \-\@\.\,])+/', '', $_POST['cc_email'    ]);
        $envelope_args = [
            'signer_email' => $signer_email,
            'signer_name' => $signer_name,
            'cc_email' => $cc_email,
            'cc_name' => $cc_name,
            'status' => 'sent'
        ];
        $args = [
            'account_id' => $_SESSION['ds_account_id'],
            'base_path' => $_SESSION['ds_base_path'],
            'ds_access_token' => $_SESSION['ds_access_token'],
            'envelope_args' => $envelope_args
        ];

        return $args;
    }
}


