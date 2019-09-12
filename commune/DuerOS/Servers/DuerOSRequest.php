<?php

/**
 * Class DuerOSRequest
 * @package Commune\DuerOS\Servers
 */

namespace Commune\DuerOS\Servers;


use Commune\Chatbot\App\Messages\Media\Audio;
use Commune\Chatbot\App\Messages\Text;
use Commune\Chatbot\Blueprint\Conversation\ConversationMessage;
use Commune\Chatbot\Blueprint\Conversation\NLU;
use Commune\Chatbot\Blueprint\Message\Message;
use Commune\Chatbot\Blueprint\Message\SSML;
use Commune\Chatbot\Blueprint\Message\VerboseMsg;
use Commune\Chatbot\App\Messages\Events\QuitEvt;
use Commune\Chatbot\App\Messages\Events\StartEvt;
use Commune\DuerOS\Constants\EndSession;
use Commune\DuerOS\DuerOSComponent;
use Commune\DuerOS\Events\DialogComplete;
use Commune\DuerOS\Messages\AbsCard;
use Commune\DuerOS\Messages\AbsDirective;
use Commune\DuerOS\Messages\RePrompt;
use Commune\DuerOS\Templates\AbstractTemp;
use Commune\Hyperf\Foundations\Options\HyperfBotOption;
use Commune\Hyperf\Foundations\Requests\AbstractMessageRequest;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Baidu\Duer\Botsdk\Request as DuerRequest;
use Baidu\Duer\Botsdk\Response as DuerResponse;
use Hyperf\HttpMessage\Server\Request as Psr7Request;
use Swoole\Server;

/**
 * @method SwooleRequest getInput()
 */
class DuerOSRequest extends AbstractMessageRequest
{

    /*--------- property ---------*/

    /**
     * @var string
     */
    protected $rawInput;

    /**
     * @var SwooleResponse
     */
    protected $response;

    /**
     * @var DuerOSComponent
     */
    protected $duerOSOption;

    /**
     * @var DuerRequest
     */
    protected $duerRequest;

    /**
     * @var DuerResponse
     */
    protected $duerResponse;

    /*--------- cached ---------*/

    /**
     * @var string|null;
     */
    protected $sessionId;

    /**
     * @var NLU
     */
    protected $nlu;

    /**
     * @var string
     */
    protected $traceId;


    /**
     * @var DuerOSCertificate
     */
    protected $certificate;

    /**
     * @var DuerOSNLUParser
     */
    protected $nluParser;

    /*--------- output --------*/

    /**
     * 永远是 ssml
     * @var string
     */
    public $outSpeech = '';

    /**
     * @var array
     */
    public $directives = [];

    /**
     * @var array
     */
    public $cards = [];

    /**
     * @var string
     */
    public $rePrompt;



    /**
     * DuerOSRequest constructor.
     * @param HyperfBotOption $option
     * @param DuerOSComponent $duerOSOption
     * @param Server $server
     * @param SwooleRequest $input
     * @param SwooleResponse $response
     * @param string $privateKeyContent
     */
    public function __construct(
        HyperfBotOption $option,
        DuerOSComponent $duerOSOption,
        Server $server,
        SwooleRequest $input,
        SwooleResponse $response,
        string $privateKeyContent
    )
    {

        $this->response = $response;
        $this->duerOSOption = $duerOSOption;
        $this->response->header('Content-Type', 'application/json;charset=utf-8');
        parent::__construct($option, $input, $input->fd, $server);

        $rawInput = static::fetchRawInputOfRequest($input);
        $this->rawInput = $rawInput;

        $this->certificate = new DuerOSCertificate(
            $privateKeyContent,
            $input->server,
            $rawInput
        );

        $this->duerRequest = static::wrapBotRequest($rawInput);
        $this->duerResponse = static::wrapBotResponse($this->duerRequest);

        $this->duerResponsePolicy();
        $this->nluParser = new DuerOSNLUParser($this->duerRequest);

        // 默认回复
        $this->rePrompt = $this->duerOSOption->rePrompt;
    }

    /**
     * duer os 响应的默认策略.
     * @see AbstractTemp   看看基础策略怎么实现的.
     */
    protected function duerResponsePolicy()
    {
        // 默认多轮对话不结束, 除非主动返回 quit
        $this->duerResponse->setShouldEndSession(false);

        // 默认关闭dueros 的聆听.
        // 除非主动用 question 的方式来进行对话, 在模板中响应.
        $this->duerResponse->setExpectSpeech(false);
    }

    /**
     * @return DuerOSCertificate
     */
    public function getCertificate(): DuerOSCertificate
    {
        return $this->certificate;
    }

    public function verify() : bool
    {
        // todo 埋点做记录
        return $this->certificate->verifyRequest();
    }

    public function illegalResponse() :void
    {
        $this->response->end($this->duerResponse->illegalRequest());
    }

    public static function fetchRawInputOfRequest(SwooleRequest $request) : string
    {
        $psr7Request = Psr7Request::loadFromSwooleRequest($request);
        // prepare duer os bot request
        $rawInput = $psr7Request->getBody()->getContents();
        return $rawInput;
    }

    public static function wrapBotRequest(string $rawInput) : DuerRequest
    {
        $rawInput = str_replace("", "", $rawInput);
        $postData = json_decode($rawInput, true);
        return new DuerRequest($postData);
    }

    public static function wrapBotResponse(DuerRequest $request) : DuerResponse
    {
        return new DuerResponse(
            $request,
            $request->getSession(),
            $request->getNlu()
        );
    }

    /**
     * @return DuerRequest
     */
    public function getDuerRequest(): DuerRequest
    {
        return $this->duerRequest;
    }


    /**
     * @param ConversationMessage[] $messages
     */
    protected function renderChatMessages(array $messages): void
    {
        foreach ($messages as $message) {
            $this->renderMessage($message);
        }
    }

    protected function renderMessage(ConversationMessage $reply) : void
    {
        $message = $reply->getMessage();

        // audio
        if ($message instanceof Audio) {
            $url = $message->getSource();
            $this->outSpeech .= '<audio src ="'.$url.'"></audio>';

        // ssml
        } elseif ($message instanceof SSML) {
            $this->outSpeech .= $message->getFormatted();

        // verbose
        } elseif ($message instanceof VerboseMsg) {
            $this->outSpeech .= PHP_EOL . $message->getText();

        // 有特殊的 Reprompt
        } elseif ($message instanceof RePrompt) {
            $this->rePrompt = $message->getText();

        // 命令
        } elseif ($message instanceof AbsDirective) {
            $this->directives[] = $message->toDirectiveArray();

        // 卡片
        } elseif ($message instanceof AbsCard) {
            $this->cards[] = $message->toCardArray();
        }

        // todo 还有模板. body template

        // 其它情况暂不处理.
    }

    protected function flushResponse(): void
    {
        $data = [
            'reprompt' => $this->rePrompt
        ];

        if (!empty($this->outSpeech)) {
            $data['outputSpeech'] = '<speak>'.trim($this->outSpeech) .'</speak>';
        }

        if (!empty($this->directives)) {
            $data['directives'] = $this->directives;
        }

        if (!empty($this->cards)) {
            $data['card'] = $this->cards;
        }

        $output =$this->duerResponse->build($data);

        // 触发事件, 可用于记录来回消息.
        $event = new DialogComplete(
            $this->conversation->getTraceId(),
            $this->rawInput,
            $output
        );
        $this->conversation->fire($event);

        // 完成渲染并退出.
        $this->response->end($output);
    }

    public function getPlatformId(): string
    {
        return DuerOSServer::class;
    }

    public function fetchUserId(): string
    {
        return $this->duerRequest->getUserId() ?? '';
    }

    public function fetchUserName(): string
    {
        //todo 未来实现api
        return '';
    }

    /**
     * todo 需要设计api调用.
     * @return array
     */
    public function fetchUserData(): array
    {
        return $this->duerRequest->getUserInfo() ?? [];
    }

    /**
     * @param SwooleRequest $input   ignore
     * @return Message
     */
    protected function makeInputMessage($input): Message
    {
        if ($this->duerRequest->isLaunchRequest()) {
            return new StartEvt();
        }

        if ($this->duerRequest->isSessionEndedRequest()) {
            $this->handleEndSession();
            return new QuitEvt();
        }

        return new Text($this->duerRequest->getQuery());
    }

    public function fetchNLU(): ? NLU
    {
        return $this->nlu ?? $this->nlu = $this->nluParser->parseNLU();

    }

    public function fetchMessageId(): string
    {
        return $this->messageId = $this->duerRequest->getLogId() ?? $this->generateMessageId();
    }

    public function fetchSessionId(): ? string
    {
        return $this->sessionId
            //todo bot-sdk 开发规范不够好. 可能出现类型问题.
            ?? $this->sessionId = $this->duerRequest->getSession()->sessionId;
    }


    /**
     * @return DuerResponse
     */
    public function getDuerResponse(): DuerResponse
    {
        return $this->duerResponse;
    }


    protected function handleEndSession() : void
    {
        $data = $this->duerRequest->getData();
        $reason = $data['request']['reason'] ?? '';

        switch ($reason) {
            case EndSession::EXCEEDED_MAX_REPROMPTS:
                $this->warn("end session because of $reason");

                break;
            case EndSession::ERROR :
                $error = $data['request']['error']['type'] ?? '';
                $message = $data['request']['error']['message'] ?? '';
                $this->handleErrorEndSession($error, $message);
                break;
            case EndSession::USER_INITIATED :
            default:
                return;
        }

    }

    protected function handleErrorEndSession(string $error, string $message) : void
    {
        switch ($error) {
            case EndSession::ERROR_INVALID_RESPONSE:
                $this->error("end session because of $error, $message");
                break;

            case EndSession::ERROR_DEVICE_COMMUNICATION_ERROR:
            case EndSession::ERROR_INTERNAL_ERROR:
            default:
                $this->warn("end session because of $error, $message");
        }

    }

    protected function warn(string $message, array $context = []) : void
    {
        $this->conversation
            ->getLogger()
            ->warning(
                "DuerOS warning, $message",
                $this->wrapContext($context)
            );
    }

    protected function error(string $message, array $context = []) : void
    {
        $this->conversation
            ->getLogger()
            ->error(
                "DuerOS error, $message",
                $this->wrapContext($context)
            );

    }

    protected function wrapContext(array $context) : array
    {
        return [
            'name' => $this->duerOSOption->name,
            'sessionId' => $this->getDuerRequest()->getSession()->sessionId,
            'requestId' => $this->getDuerRequest()->getLogId(),
            'userId' => $this->getDuerRequest()->getUserId(),
        ] + $context;
    }
}