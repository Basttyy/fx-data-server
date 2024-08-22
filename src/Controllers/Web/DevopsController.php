<?php

namespace Basttyy\FxDataServer\Controllers\Web;

use Basttyy\FxDataServer\Controllers\Controller;
use Basttyy\FxDataServer\libs\LogViewer\LogViewer;
use Basttyy\FxDataServer\libs\Request;
use Basttyy\FxDataServer\libs\Storage\DbCache;
use Basttyy\FxDataServer\Models\User;
use Illuminate\Support\Facades\Crypt;

// if (class_exists("\\Illuminate\\Routing\\Controller")) {	
//     class BaseController extends \Illuminate\Routing\Controller {}	
// } elseif (class_exists("Laravel\\Lumen\\Routing\\Controller")) {	
//     class BaseController extends \Laravel\Lumen\Routing\Controller {}	
// }

/**
 * Class LogViewerController
 * @package Rap2hpoutre\LaravelLogViewer
 */
class DevopsController extends Controller
{
    /**
     * @var LogViewer
     */
    private $log_viewer;

    /**
     * @var string
     */
    protected $view_log = 'log';
    protected $login_view = 'login';


    /**
     * LogViewerController constructor.
     */
    public function __construct()
    {
    }

    public function viewLogin(Request $request)
    {
        return response()->view($this->login_view);
    }

    public function login(Request $request)
    {
        $email = $request->input('email');
        $password = $request->input('password');

        $user = User::getBuilder()->findByEmail($email, false);

        if ($user && password_verify($password, $user->password)) {
            $_SESSION['user_id'] = $user->id;
            header('Location: /devops/error-logs');
            exit();
        }

        $_SESSION['error'] = 'Invalid credentials';
        header('Location: /devops/login');
        exit();
    }

    public function logout() {
        session_destroy();
        header('Location: /devops/login');
        exit();
    }

    /**
     * @return array|mixed
     * @throws \Exception
     */
    public function logViewer(Request $request)
    {
        $this->log_viewer = new LogViewer();

        $folderFiles = [];
        if ($request->has('f')) {
            $this->log_viewer->setFolder(decrypt($request->query('f')));
            $folderFiles = $this->log_viewer->getFolderFiles(true);
        }
        if ($request->has('l')) {
            $this->log_viewer->setFile(decrypt($request->query('l')));
        }

        if ($early_return = $this->earlyReturn($request)) {
            return $early_return;
        }

        $data = [
            'sidebar_content' => 'components/log-sidebar.blade.php',
            'title' => 'Logs Viewer',
            'logs' => $this->log_viewer->all(),
            'folders' => $this->log_viewer->getFolders(),
            'current_folder' => $this->log_viewer->getFolderName(),
            'folder_files' => $folderFiles,
            'files' => $this->log_viewer->getFiles(true),
            'current_file' => $this->log_viewer->getFileName(),
            'standardFormat' => true,
            'structure' => $this->log_viewer->foldersAndFiles(),
            'storage_path' => $this->log_viewer->getStoragePath(),
        ];

        if ($request->wantsJson()) {
            return $data;
        }

        if (is_array($data['logs']) && count($data['logs']) > 0) {
            $firstLog = reset($data['logs']);
            if ($firstLog) {
                if (!$firstLog['context'] && !$firstLog['level']) {
                    $data['standardFormat'] = false;
                }
            }
        }

        return response()->view($this->view_log, $data);
    }

    /**
     * @return bool|mixed
     * @throws \Exception
     */
    private function earlyReturn(Request $request)
    {
        if ($request->has('f')) {
            $this->log_viewer->setFolder(decrypt($request->query('f')));
        }

        if ($request->has('dl')) {
            return $this->download($this->pathFromInput('dl', $request));
        } elseif ($request->has('clean')) {
            storage( cache: new DbCache)->put($this->pathFromInput('clean', $request), '', true);
            return $this->redirect(url()->previous());
        } elseif ($request->has('del')) {
            storage( cache: new DbCache)->delete($this->pathFromInput('del', $request), true);
            return $this->redirect($request->url());
        } elseif ($request->has('delall')) {
            $files = ($this->log_viewer->getFolderName())
                        ? $this->log_viewer->getFolderFiles(true)
                        : $this->log_viewer->getFiles(true);
            foreach ($files as $file) {
                storage( cache: new DbCache)->delete($this->log_viewer->pathToLogFile($file), true);
            }
            return $this->redirect($request->url());
        }
        return false;
    }

    /**
     * @param string $input_string
     * @return string
     * @throws \Exception
     */
    private function pathFromInput($input_string, $request)
    {
        return $this->log_viewer->pathToLogFile(decrypt($request->query($input_string)));
    }

    /**
     * @param $to
     * @return bool
     */
    private function redirect($to)
    {
        if (function_exists('redirect')) {
            return response()->redirect($to);
        }

        // return app('redirect')->to($to);
    }

    /**
     * @param string $data
     * @return bool
     */
    private function download($data)
    {
        if (function_exists('response')) {
            return response()->download($data);
        }
    }
}