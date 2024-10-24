<?php
namespace App\Http\Controllers\Api;

use Exception;
use Eyika\Atom\Framework\Http\Request;
use Eyika\Atom\Framework\Http\Response;
use Eyika\Atom\Framework\Support\Storage\Storage;

final class ChartStoreController
{
    private const base_folder = 'chartstore';

    const CHART_LAYOUTS = 'chart_layouts';
    const CAHRT_TEMPLATES = 'chart_templates';
    const STUDY_TEMPLATES = 'study_templates';
    const DRAWING_TEMPLATES = 'drawing_templates';
    const LINE_AND_GROUP_TOOLS = 'line_and_group_tools';

    public function getAllCharts(Request $request)
    {
        try {
            $user = $request->auth_user;
            $storage = storage('local');
            $filepaths = $storage->allFiles(self::base_folder.'/'.self::CHART_LAYOUTS."/user_$user->id");
            $charts = [];
    
            foreach ($filepaths as $path) {
                $data = json_decode($storage->get($path), true);
                if (!empty($data))
                    $charts[] = $data;
            }

            return Response::json('charts retrieved success', $charts);
        } catch (Exception $e) {
            return Response::json('unable to retrieve charts', 404);
        }
    }

    public function getChartContent (Request $request, string $id)
    {
        try {
            $user = $request->auth_user;
            $storage = storage('local');
            $chart = $storage->get(self::base_folder.'/'.self::CHART_LAYOUTS."/user_$user->id/chart_".$id.'.json');
    
            if (!$chart = json_decode($chart, true)) {
                throw new Exception();
            }
    
            return Response::json('chart retrieved success', $chart);
        } catch (Exception $e) {
            return Response::json('unable to retrieve chart content', 404);
        }
    }

    public function saveChart (Request $request)
    {
        try {
            $user = $request->auth_user;
            $storage = storage('local');
            $path = self::base_folder.'/'.self::CHART_LAYOUTS."/user_$user->id";
            $id = $this->generateId($path, $storage);
            $data = [ ...$request->input(), 'id' => $id ];
            $status = $storage->put($path."/chart_".$id.'.json', json_encode($data));
    
            if (!$status)
                throw new Exception();
    
            return Response::json('chart saved success', [
                'id' => $id
            ]);
        } catch (Exception $e) {
            logger()->info($e->getMessage(). '\n' . $e->getTraceAsString());
            return Response::json('unable to save chart', 404);
        }
    }

    public function updateChart (Request $request, string $id)
    {
        try {
            $user = $request->auth_user;
            $storage = storage('local');
            $path = self::base_folder.'/'.self::CHART_LAYOUTS."/user_$user->id";
            $data = [ ...$request->input(), 'id' => $id ];
            $status = $storage->put($path."/chart_".$id.'.json', json_encode($data));
    
            if (!$status)
                throw new Exception();
    
            return Response::json('chart updated success', [
                'id' => $id
            ], 201);
        } catch (Exception $e) {
            logger()->info($e->getMessage(). '\n' . $e->getTraceAsString());
            return Response::json('unable to update chart', 404);
        }
    }

    public function removeChart (Request $request, string $id)
    {
        try {
            $user = $request->auth_user;
            $storage = storage('local');
            $path = self::base_folder.'/'.self::CHART_LAYOUTS."/user_$user->id";
            $status = $storage->delete($path."/chart_".$id.'.json');
    
            if (!$status)
                throw new Exception();
    
            return Response::json('chart removed success');
        } catch (Exception $e) {
            return Response::json('unable to remove chart', 404);
        }
    }

    public function getChartTemplateContent (Request $request, string $name)
    {
        try {
            $user = $request->auth_user;
            $storage = storage('local');
            $chart_template = $storage->get(self::base_folder.'/'.self::CAHRT_TEMPLATES."/user_$user->id/chart_template_".$name.'.json');
            if (!$template = json_decode($chart_template, true))
                throw new Exception();
    
            return Response::json('chart template retrieved success', $template);
        } catch (Exception $e) {
            return Response::json('unable to retrieve chart template content', 404);
        }
    }
    
    public function getAllChartTemplates (Request $request)
    {
        try {
            $user = $request->auth_user;
            $storage = storage('local');
            $filepaths = $storage->allFiles(self::base_folder.'/'.self::CAHRT_TEMPLATES."/user_$user->id");
            $charts = [];
    
            foreach ($filepaths as $path) {
                $charts[] = json_decode($storage->get($path), true);
            }
    
            return Response::json('chart templates retrieved success', $charts);
        } catch (Exception $e) {
            return Response::json('unable to retrieve chart templates', 404);
        }
    }

    public function saveChartTemplate (Request $request)
    {
        try {
            $user = $request->auth_user;
            $storage = storage('local');
            $path = self::base_folder.'/'.self::CAHRT_TEMPLATES."/user_$user->id";
            // $id = $this->generateId($path, $storage);
            $name = $request->input('name');
            $status = $storage->put($path."/chart_template_".$name.'.json', json_encode($request->input()));
    
            if (!$status)
                throw new Exception();
    
            return Response::json('chart template saved success', [
                'name' => $name
            ]);
        } catch (Exception $e) {
            return Response::json('unable to save chart template', 404);
        }
    }

    public function removeChartTemplate (Request $request, string $name)
    {
        try {
            $user = $request->auth_user;
            $storage = storage('local');
            $path = self::base_folder.'/'.self::CAHRT_TEMPLATES."/user_$user->id";
            $status = $storage->delete($path."/chart_template_".$name.'.json');
    
            if (!$status)
                throw new Exception();
    
            return Response::json('chart template removed success');
        } catch (Exception $e) {
            return Response::json('unable to remove chart template', 404);
        }
    }

    public function getAllStudyTemplates (Request $request)
    {
        try {
            $user = $request->auth_user;
            $storage = storage('local');
            $filepaths = $storage->allFiles(self::base_folder.'/'.self::STUDY_TEMPLATES."/user_$user->id");
            $templates = [];
    
            foreach ($filepaths as $path) {
                $data = json_decode($storage->get($path), true);
                if ($data)
                    $templates[] = $data;
            }
    
            return Response::json('study templates retrieved success', $templates);
        } catch (Exception $e) {
            return Response::json('unable to retrieve study template', 404);
        }
    }

    public function removeStudyTemplate (Request $request, string $name)
    {
        try {
            $user = $request->auth_user;
            $storage = storage('local');
            $path = self::base_folder.'/'.self::STUDY_TEMPLATES."/user_$user->id";
            $status = $storage->delete($path."/study_template_".$name.'.json');
    
            if (!$status)
                throw new Exception();
    
            return Response::json('study template removed success');
        } catch (Exception $e) {
            return Response::json('unable to remove study template', 404);
        }
    }

    public function saveStudyTemplate (Request $request)
    {
        try {
            $user = $request->auth_user;
            $storage = storage('local');
            $path = self::base_folder.'/'.self::STUDY_TEMPLATES."/user_$user->id";
            // $id = $this->generateId($path, $storage);
            $name = $request->input('name');
            $status = $storage->put($path."/study_template_".$name.'.json', json_encode($request->input()));
    
            if (!$status)
                throw new Exception();
    
            return Response::json('study template saved success', [
                'name' => $name
            ]);
        } catch (Exception $e) {
            return Response::json('unable to save study template', 404);
        }
    }

    public function getStudyTemplateContent (Request $request, string $name)
    {
        try {
            $user = $request->auth_user;
            $storage = storage('local');
            $chart_template = $storage->get(self::base_folder.'/'.self::STUDY_TEMPLATES."/user_$user->id/study_template_".$name.'.json');
            if (!$template = json_decode($chart_template, true)) {
                throw new Exception();
            }
    
            return Response::json('study template retrieved success', $template);
        } catch (Exception $e) {
            return Response::json('unable to retrieve study template', 404);
        }
    }

    public function getDrawingTemplates (Request $request, $name)
    {
        try {
            $user = $request->auth_user;
            $storage = storage('local');
            $filepaths = $storage->allFiles(self::base_folder.'/'.self::DRAWING_TEMPLATES."/user_$user->id");
            $templates = [];
    
            foreach ($filepaths as $path) {
                $name = json_decode($storage->get($path), true)->name ?? null;
                if ($name)
                    $templates[] = $name;
            }
    
            return Response::json('drawing templates retrieved success', $templates);
        } catch (Exception $e) {
            return Response::json('unable to retrieve drawing templates', 404);
        }
    }

    public function removeDrawingTemplate (Request $request, string $tool_name, string $template_name)
    {
        try {
            $user = $request->auth_user;
            $storage = storage('local');
            $path = self::base_folder.'/'.self::STUDY_TEMPLATES."/user_$user->id";
            $status = $storage->delete($path."/drawing_template_".$tool_name.'_'.$template_name.'.json');
    
            if (!$status)
                throw new Exception();
    
            return Response::json('study template removed success');
        } catch (Exception $e) {
            return Response::json('unable to remove drawing template', 404);
        }
    }

    public function loadDrawingTemplate (Request $request, string $tool_name, string $template_name)
    {
        try {
            $user = $request->auth_user;
            $storage = storage('local');
            $drawing_template = $storage->get(self::base_folder.'/'.self::STUDY_TEMPLATES."/user_$user->id/drawing_template_".$tool_name.'_'.$template_name.'.json');
            if (!$template = json_decode($drawing_template, true)) {
                throw new Exception();
            }
    
            return Response::json('drawing template retrieved success', $template);
        } catch (Exception $e) {
            return Response::json('unable to retrieve drawing template', 404);
        }
    }

    public function saveDrawingTemplate (Request $request)
    {
        try {
            $user = $request->auth_user;
            $storage = storage('local');
            // $id = $this->generateId($path, $storage);
            $toolname = $request->input('tool_name');
            $templatename = $request->input('template_name');
    
            $path = self::base_folder.'/'.self::STUDY_TEMPLATES."/user_$user->id";
            $data = json_encode($request->input());
            if (!$data)
                throw new Exception();
    
            if (!$storage->put($path."/drawing_template_".$toolname.'_'.$templatename.'.json', $data))
                throw new Exception();
    
            return Response::json('study template saved success', [
                'name' => $templatename
            ]);
        } catch (Exception $e) {
            return Response::json('unable to save drawing template', 404);
        }
    }

    public function saveLineToolsAndGroups (Request $request)
    {
        try {
            $user = $request->auth_user;
            $storage = storage('local');
            // $id = $this->generateId($path, $storage);
            $layoutid = $request->input('layoutId');
            $chartid = $request->input('chartId');
    
            $path = self::base_folder.'/'.self::LINE_AND_GROUP_TOOLS."/user_$user->id";
            $data = json_encode($request->only(['layoutId', 'chartId', 'symbol', 'sources', 'groups']));
            if (!$data)
                throw new Exception();
    
            if (!$storage->put($path."/line_tools_".$layoutid.'_'.$chartid.'.json', $data))
                throw new Exception();
    
            return Response::json('line tools saved success', [
                'id' => $chartid
            ]);
        } catch (Exception $e) {
            return Response::json('unable to retrieve chart content', 404);
        }
    }

    public function loadLineToolsAndGroups (Request $request, string $layoutid, string $chartid)
    {
        try {
            $user = $request->auth_user;
            $storage = storage('local');
            $line_tools = $storage->get(self::base_folder.'/'.self::LINE_AND_GROUP_TOOLS."/user_$user->id/line_tools_".$layoutid.'_'.$chartid.'.json');
            if (!$line_tools = json_decode($line_tools, true)) {
                throw new Exception();
            }
    
            return Response::json('line tools retrieved success', $line_tools);
        } catch (Exception $e) {
            return Response::json('unable to retrieve line tools', 404);
        }
    }

    private function generateId(string $path, Storage $storage)
    {
        $id = count($storage->allFiles($path)) + 1;

        return $id;
    }
}